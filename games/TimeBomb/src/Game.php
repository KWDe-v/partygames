<?php
declare(strict_types=1);

namespace Games\TimeBomb;

use App\Core\ApiException;
use App\Core\Input;
use App\Core\Text;
use App\Platform\RoundGame;

/**
 * "A Bomba Relógio"
 *
 * Rodada (uma categoria): turn ─▶ turn ─▶ ... ─▶ reveal ─▶ próxima rodada
 * Cada fase 'turn' tem o prazo oficial do servidor (turn_time). Só o jogador da vez responde;
 * resposta válida passa a bomba adiante. Se o prazo vence, a bomba explode: penalidade e
 * eliminação da rodada. A rodada termina quando resta 1 jogador (ou ninguém).
 *
 * Respostas ficam em game_entries com kind "t<nº do turno>": o UNIQUE do banco garante uma
 * resposta por turno, mesmo com requisições simultâneas. Repetição é verificada pela forma
 * "dobrada" (sem acento/maiúsculas) de todas as respostas aceitas na rodada.
 */
final class Game extends RoundGame
{
    private const MAX_LEN = 60;
    private const MAX_TURNS = 300;

    public function normalizeSettings(array $s): array
    {
        if (!$s['categories'] && !$s['custom']) {
            throw new ApiException('Escolha pelo menos uma categoria ou escreva categorias personalizadas.', 422, 'invalid_input');
        }
        return $s;
    }

    protected function planRound(array $room, array $players, int $number, int $now): array
    {
        $labels = require dirname(__DIR__) . '/content/categories.php';
        $pool = [];
        foreach ((array) $this->ctx->setting($room, 'categories', []) as $key) {
            if (isset($labels[$key])) {
                $pool[] = ['key' => $key, 'label' => $labels[$key]];
            }
        }
        foreach ((array) $this->ctx->setting($room, 'custom', []) as $label) {
            $pool[] = ['key' => 'custom:' . md5($label), 'label' => $label];
        }
        $pick = $this->pickPrompt($room, $pool);
        $order = self::shuffle(array_map('intval', array_column($players, 'id')));
        return [
            'phase' => 'turn',
            'seconds' => $this->int($room, 'turn_time', 12),
            'prompt_key' => $pick['key'],
            'public' => [
                'category' => $pick['label'],
                'order' => $order,
                'alive' => $order,
                'turn' => 1,
                'current' => $order[0],
                'history' => [],
            ],
        ];
    }

    protected function step(array $room, array $round, int $now): bool
    {
        if ($round['phase'] === 'reveal') {
            if (!$this->expired($round, $now)) {
                return false;
            }
            $this->finishRound($room, $round, $now);
            return true;
        }
        if ($round['phase'] !== 'turn') {
            return false;
        }
        $pub = $round['public'];
        $players = $this->repo()->players((int) $round['id']);
        $current = (int) $pub['current'];
        $left = !($players[$current]['active'] ?? false);
        if (!$left && !$this->expired($round, $now)) {
            return false;
        }
        // Quem saiu da sala apenas deixa a rodada; quem deixou o tempo acabar explode.
        $pub['alive'] = array_values(array_diff($pub['alive'], [$current]));
        if (!$left) {
            $number = (int) $round['number'];
            $penalty = $this->int($room, 'penalty', 2);
            if ($penalty > 0) {
                $this->ctx->award($room, $number, $current, -$penalty, 'A bomba explodiu na sua mão', "r$number:boom");
            }
            $pub['history'][] = ['pid' => $current, 'nickname' => (string) $players[$current]['nickname'], 'text' => null, 'boom' => true];
        }
        $this->passBomb($room, $round, $pub, $current, $now);
        return true;
    }

    /** Passa a bomba ao próximo jogador vivo ou encerra a rodada. */
    private function passBomb(array $room, array $round, array $pub, int $from, int $now): void
    {
        $players = $this->repo()->players((int) $round['id']);
        $alive = array_values(array_filter($pub['alive'], static function ($id) use ($players): bool {
            return !empty($players[(int) $id]['active']);
        }));
        $pub['alive'] = $alive;
        if (count($alive) <= 1 || $pub['turn'] >= self::MAX_TURNS) {
            $this->reveal($room, $round, $pub, $players, $now);
            return;
        }
        $order = $pub['order'];
        $pos = array_search($from, $order, true);
        $next = null;
        for ($i = 1; $i <= count($order); $i++) {
            $candidate = (int) $order[($pos + $i) % count($order)];
            if (in_array($candidate, $alive, true)) {
                $next = $candidate;
                break;
            }
        }
        $pub['turn']++;
        $pub['current'] = $next;
        $this->repo()->savePublic((int) $round['id'], $pub);
        $this->phase($room, $round, 'turn', $this->int($room, 'turn_time', 12), $now);
    }

    private function reveal(array $room, array $round, array $pub, array $players, int $now): void
    {
        $number = (int) $round['number'];
        $survivors = $pub['alive'];
        foreach ($survivors as $pid) {
            $this->ctx->award($room, $number, (int) $pid, $this->int($room, 'points_survivor', 3), 'Sobreviveu à bomba', "r$number:survivor");
        }
        $answers = count(array_filter($pub['history'], static function (array $h): bool {
            return empty($h['boom']);
        }));
        $pub['result'] = ['survivors' => array_map(static function ($id) use ($players): string {
            return (string) ($players[(int) $id]['nickname'] ?? '?');
        }, $survivors), 'answers' => $answers];
        $this->repo()->savePublic((int) $round['id'], $pub);
        $this->ctx->event($room, $number, 'Categoria: ' . $pub['category'], sprintf(
            '%d respostas. %s',
            $answers,
            $survivors ? 'Sobrevivente: ' . self::names($players, $survivors) . '.' : 'Ninguém sobreviveu.'
        ), "r$number");
        $this->phase($room, $round, 'reveal', $this->int($room, 'reveal_time', 10), $now);
    }

    protected function handle(string $op, array $room, array $round, array $player, Input $in): array
    {
        if ($op !== 'answer') {
            throw new ApiException('Ação desconhecida.', 400, 'invalid_op');
        }
        $this->requirePhase($round, 'turn', 'Não há bomba em jogo agora.');
        $pid = (int) $player['id'];
        $pub = $round['public'];
        if ($pid !== (int) $pub['current']) {
            throw new ApiException('Calma! A bomba não está com você.', 403, 'not_your_turn');
        }
        $text = Text::limited($in->string('text', 600, 'resposta'), 1, self::MAX_LEN, 'A resposta');
        if ($this->ctx->setting($room, 'no_repeat', true)) {
            $folded = Text::fold($text);
            foreach ($pub['history'] as $h) {
                if ($h['text'] !== null && Text::fold((string) $h['text']) === $folded) {
                    throw new ApiException('"' . $text . '" já foi dito nesta rodada. Tente outra!', 409, 'repeated');
                }
            }
        }
        $now = time();
        $number = (int) $round['number'];
        $this->repo()->addEntry((int) $round['id'], $pid, 't' . (int) $pub['turn'], $text, [], $now);
        $this->ctx->award($room, $number, $pid, $this->int($room, 'points_answer', 1), 'Resposta na bomba relógio', "r$number:t" . (int) $pub['turn']);
        $pub['history'][] = ['pid' => $pid, 'nickname' => (string) $player['nickname'], 'text' => $text, 'boom' => false];
        $this->passBomb($room, $round, $pub, $pid, $now);
        return ['answer' => $text];
    }

    protected function roundView(array $room, array $round, array $player, array $roundPlayers): array
    {
        $pub = $round['public'];
        $pid = (int) $player['id'];
        $players = [];
        $progress = [];
        foreach ($pub['order'] as $id) {
            $alive = in_array($id, $pub['alive'], true);
            $players[] = ['id' => (int) $id, 'nickname' => (string) ($roundPlayers[(int) $id]['nickname'] ?? '?'), 'alive' => $alive, 'current' => (int) $id === (int) $pub['current'] && $round['phase'] === 'turn'];
            $progress[(int) $id] = $alive ? ((int) $id === (int) $pub['current'] ? 'pending' : 'submitted') : 'excluded';
        }
        return [
            'category' => (string) $pub['category'],
            'turn' => (int) $pub['turn'],
            'current' => $round['phase'] === 'turn' ? (int) $pub['current'] : null,
            'my_turn' => $round['phase'] === 'turn' && (int) $pub['current'] === $pid,
            'alive' => in_array($pid, $pub['alive'], true),
            'order' => $players,
            'history' => array_slice(array_reverse($pub['history']), 0, 40),
            'no_repeat' => (bool) $this->ctx->setting($room, 'no_repeat', true),
            'max_len' => self::MAX_LEN,
            'result' => $round['phase'] === 'reveal' ? ($pub['result'] ?? null) : null,
            'progress' => $progress,
        ];
    }
}
