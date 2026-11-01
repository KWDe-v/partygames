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
        if ($op === 'answer') {
            return $this->doAnswer($room, $round, $player, $in);
        }
        if ($op === 'reject') {
            return $this->doReject($room, $round, $player, $in);
        }
        throw new ApiException('Ação desconhecida.', 400, 'invalid_op');
    }

    private function doAnswer(array $room, array $round, array $player, Input $in): array
    {
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
        $turn = (int) $pub['turn'];
        $this->repo()->addEntry((int) $round['id'], $pid, 't' . $turn, $text, [], $now);
        $this->ctx->award($room, $number, $pid, $this->int($room, 'points_answer', 1), 'Resposta na bomba relógio', "r$number:t" . $turn);
        $pub['history'][] = ['pid' => $pid, 'nickname' => (string) $player['nickname'], 'text' => $text, 'boom' => false, 'turn' => $turn];
        $this->passBomb($room, $round, $pub, $pid, $now);
        return ['answer' => $text];
    }

    /**
     * Recusa coletiva: qualquer jogador vivo pode recusar a resposta de outro. Quando TODOS os
     * demais jogadores vivos recusam a mesma resposta, quem a escreveu explode na hora e perde
     * o ponto daquela resposta. O voto é único por jogador (UNIQUE em game_votes).
     */
    private function doReject(array $room, array $round, array $player, Input $in): array
    {
        $this->requirePhase($round, 'turn', 'A rodada não está em andamento.');
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        $pub = $round['public'];
        $turn = $in->int('turn', 1, 1000000, null, 'resposta');

        $entry = null;
        foreach ($pub['history'] as $h) {
            if (empty($h['boom']) && (int) ($h['turn'] ?? -1) === $turn) {
                $entry = $h;
                break;
            }
        }
        if ($entry === null) {
            throw new ApiException('Essa resposta não existe mais.', 404, 'not_found');
        }
        $target = (int) $entry['pid'];
        if ($target === $pid) {
            throw new ApiException('Você não pode recusar a própria resposta.', 403, 'own_answer');
        }
        if (!in_array($pid, $pub['alive'], true)) {
            throw new ApiException('Você está fora da rodada.', 403, 'out');
        }
        $players = $this->repo()->players($roundId);
        if (!in_array($target, $pub['alive'], true) || empty($players[$target]['active'])) {
            throw new ApiException('Esse jogador já saiu da rodada.', 409, 'gone');
        }
        $now = time();
        $this->repo()->castVote($roundId, $pid, 'rej' . $turn, (string) $target, $now);

        $aliveOthers = array_values(array_filter($pub['alive'], static function ($id) use ($players, $target): bool {
            return (int) $id !== $target && !empty($players[(int) $id]['active']);
        }));
        $rejecters = [];
        foreach ($this->repo()->votes($roundId, 'rej' . $turn) as $v) {
            if ($v['ballot'] === 'rej' . $turn && in_array((int) $v['voter_id'], $aliveOthers, true)) {
                $rejecters[(int) $v['voter_id']] = true;
            }
        }
        $count = count($rejecters);
        $need = count($aliveOthers);
        if ($need > 0 && $count >= $need) {
            $this->explodeRejected($room, $round, $pub, $players, $target, $turn, $now);
            return ['rejected' => true, 'eliminated' => $target];
        }
        $this->ctx->bump((int) $room['id'], $now);
        return ['rejected' => true, 'eliminated' => null, 'count' => $count, 'need' => $need];
    }

    /** Explode quem teve a resposta recusada por todos; passa a bomba se ela estava com ele. */
    private function explodeRejected(array $room, array $round, array $pub, array $players, int $target, int $turn, int $now): void
    {
        $number = (int) $round['number'];
        $penalty = $this->int($room, 'penalty', 2);
        if ($penalty > 0) {
            $this->ctx->award($room, $number, $target, -$penalty, 'Resposta recusada por todos', "r$number:rejboom$turn");
        }
        // A resposta foi julgada inválida: cancela o ponto que ela havia dado.
        $this->ctx->award($room, $number, $target, -$this->int($room, 'points_answer', 1), 'Resposta recusada', "r$number:rejcancel$turn");
        foreach ($pub['history'] as &$h) {
            if (empty($h['boom']) && (int) ($h['turn'] ?? -1) === $turn) {
                $h['rejected'] = true;
            }
        }
        unset($h);
        $pub['history'][] = ['pid' => $target, 'nickname' => (string) ($players[$target]['nickname'] ?? '?'), 'text' => null, 'boom' => true, 'rejected' => true];
        $pub['alive'] = array_values(array_diff($pub['alive'], [$target]));

        if ((int) $pub['current'] === $target) {
            $this->passBomb($room, $round, $pub, $target, $now);
            return;
        }
        $alive = array_values(array_filter($pub['alive'], static function ($id) use ($players): bool {
            return !empty($players[(int) $id]['active']);
        }));
        $pub['alive'] = $alive;
        if (count($alive) <= 1) {
            $this->reveal($room, $round, $pub, $players, $now);
            return;
        }
        $this->repo()->savePublic((int) $round['id'], $pub);
        $this->ctx->bump((int) $room['id'], $now);
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

        // Recusas por turno (só contam votos de quem está vivo).
        $aliveActive = array_values(array_filter($pub['alive'], static function ($id) use ($roundPlayers): bool {
            return !empty($roundPlayers[(int) $id]['active']);
        }));
        $rejByTurn = [];
        if ($round['phase'] === 'turn') {
            foreach ($this->repo()->votes((int) $round['id']) as $v) {
                if (strncmp((string) $v['ballot'], 'rej', 3) !== 0) {
                    continue;
                }
                $voter = (int) $v['voter_id'];
                if (in_array($voter, $aliveActive, true)) {
                    $rejByTurn[(int) substr((string) $v['ballot'], 3)][$voter] = true;
                }
            }
        }
        $iAmAlive = in_array($pid, $aliveActive, true);
        $history = [];
        foreach (array_slice(array_reverse($pub['history']), 0, 40) as $h) {
            if (empty($h['boom']) && isset($h['turn'])) {
                $t = (int) $h['turn'];
                $author = (int) $h['pid'];
                $others = array_values(array_filter($aliveActive, static function ($id) use ($author): bool {
                    return (int) $id !== $author;
                }));
                $rej = $rejByTurn[$t] ?? [];
                $h['reject_count'] = count($rej);
                $h['reject_need'] = count($others);
                $h['i_rejected'] = isset($rej[$pid]);
                $h['can_reject'] = $iAmAlive && $author !== $pid && in_array($author, $aliveActive, true) && !isset($rej[$pid]) && count($others) > 0;
            }
            $history[] = $h;
        }

        return [
            'category' => (string) $pub['category'],
            'turn' => (int) $pub['turn'],
            'current' => $round['phase'] === 'turn' ? (int) $pub['current'] : null,
            'my_turn' => $round['phase'] === 'turn' && (int) $pub['current'] === $pid,
            'alive' => in_array($pid, $pub['alive'], true),
            'order' => $players,
            'history' => $history,
            'no_repeat' => (bool) $this->ctx->setting($room, 'no_repeat', true),
            'max_len' => self::MAX_LEN,
            'result' => $round['phase'] === 'reveal' ? ($pub['result'] ?? null) : null,
            'progress' => $progress,
        ];
    }
}
