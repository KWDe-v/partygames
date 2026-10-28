<?php
declare(strict_types=1);

namespace Games\WhoAmI;

use App\Core\ApiException;
use App\Core\Input;
use App\Core\Text;
use App\Platform\RoundGame;

/**
 * "Quem Sou Eu?"
 *
 * Rodada: ask ─▶ answer ─▶ ask ─▶ ... ─▶ reveal
 * Na vez de um jogador ('ask'), ele pergunta (sim/não) ou palpita. Após uma pergunta, os outros
 * respondem ('answer': sim, não ou não sei — uma resposta cada, cédula "a<turno>"). A vez passa
 * ao próximo jogador ativo que ainda não acertou. A rodada termina quando todos acertarem ou
 * quando acabar o tempo total da rodada (round_end, guardado nos dados públicos).
 *
 * Privacidade: a identidade fica em game_round_players.secret. A visão monta a lista de
 * identidades OMITINDO a do próprio jogador (até ele acertar ou a revelação).
 * Palpites: kind "g<n>" (n = nº do palpite do jogador), limitados por max_guesses e sem repetir.
 */
final class Game extends RoundGame
{
    private const Q_MIN = 3;
    private const Q_MAX = 150;
    private const FAST = 3;
    private const ANSWERS = ['yes', 'no', 'idk'];

    protected function skippablePhases(): array
    {
        return ['reveal'];
    }

    protected function planRound(array $room, array $players, int $number, int $now): array
    {
        $chosen = (array) $this->ctx->setting($room, 'categories', []);
        $pool = [];
        foreach (require dirname(__DIR__) . '/content/identities.php' as $key => $cat) {
            if (in_array($key, $chosen, true)) {
                foreach ($cat['items'] as $item) {
                    $pool[] = ['name' => $item[0], 'aliases' => $item[1], 'category' => $cat['label']];
                }
            }
        }
        $pool = self::shuffle($pool);
        $ids = self::shuffle(array_map('intval', array_column($players, 'id')));
        $repeat = (bool) $this->ctx->setting($room, 'allow_repeat', false) || count($pool) < count($ids);
        $assign = [];
        foreach ($ids as $i => $pid) {
            $identity = $repeat ? $pool[random_int(0, count($pool) - 1)] : $pool[$i];
            $assign[$pid] = ['role' => 'player', 'secret' => $identity];
        }
        $roundEnd = $now + 60 * $this->int($room, 'round_minutes', 10);
        $ask = min($this->int($room, 'ask_time', 45), $roundEnd - $now);
        return [
            'phase' => 'ask',
            'seconds' => $ask,
            'prompt_key' => 'r' . $number . ':' . random_int(1, 999999),
            'public' => ['order' => $ids, 'current' => $ids[0], 'turn' => 1, 'round_end' => $roundEnd, 'solved' => [], 'log' => []],
            'players' => $assign,
        ];
    }

    /** Palpite certo? Compara a forma "dobrada" com o nome e os apelidos aceitos. */
    public static function matches(string $guess, array $identity): bool
    {
        $g = Text::fold($guess);
        if ($g === '') {
            return false;
        }
        foreach (array_merge([$identity['name']], (array) $identity['aliases']) as $form) {
            if (Text::fold((string) $form) === $g) {
                return true;
            }
        }
        return false;
    }

    protected function step(array $room, array $round, int $now): bool
    {
        $phase = (string) $round['phase'];
        if ($phase === 'reveal') {
            if (!$this->expired($round, $now)) {
                return false;
            }
            $this->finishRound($room, $round, $now);
            return true;
        }
        $pub = $round['public'];
        $roundId = (int) $round['id'];
        $players = $this->repo()->players($roundId);
        if ($now >= (int) $pub['round_end']) {
            $pub = $this->closeQuestion($round, $pub, $players);
            $this->reveal($room, $round, $pub, $players, $now);
            return true;
        }
        $current = (int) $pub['current'];
        if ($phase === 'ask') {
            if (!empty($players[$current]['active']) && !$this->expired($round, $now)) {
                return false;
            }
            $this->nextTurn($room, $round, $pub, $players, $now);
            return true;
        }
        if ($phase === 'answer') {
            $others = array_diff(self::activeIds($players), [$current]);
            $answered = array_map('intval', array_column($this->answers($roundId, (int) $pub['turn']), 'voter_id'));
            if (!$this->expired($round, $now) && array_diff($others, $answered)) {
                return false;
            }
            $pub = $this->closeQuestion($round, $pub, $players);
            $this->nextTurn($room, $round, $pub, $players, $now);
            return true;
        }
        return false;
    }

    /** Respostas de um turno (o prefixo "a1" também casaria "a10", por isso o filtro exato). */
    private function answers(int $roundId, int $turn): array
    {
        $ballot = 'a' . $turn;
        return array_values(array_filter($this->repo()->votes($roundId, $ballot), static function (array $v) use ($ballot): bool {
            return $v['ballot'] === $ballot;
        }));
    }

    /** Grava no histórico a contagem de respostas da pergunta em aberto. */
    private function closeQuestion(array $round, array $pub, array $players): array
    {
        $turn = (int) $pub['turn'];
        foreach ($pub['log'] as &$entry) {
            if ($entry['type'] === 'question' && $entry['turn'] === $turn && $entry['answers'] === null) {
                $counts = ['yes' => 0, 'no' => 0, 'idk' => 0];
                foreach ($this->answers((int) $round['id'], $turn) as $v) {
                    $counts[$v['target']] = ($counts[$v['target']] ?? 0) + 1;
                }
                $entry['answers'] = $counts;
            }
        }
        unset($entry);
        $this->repo()->savePublic((int) $round['id'], $pub);
        return $pub;
    }

    /** Próximo jogador ativo que ainda não acertou; se não houver, revelação. */
    private function nextTurn(array $room, array $round, array $pub, array $players, int $now): void
    {
        $order = $pub['order'];
        $pos = array_search((int) $pub['current'], $order, true);
        $next = null;
        for ($i = 1; $i <= count($order); $i++) {
            $cand = (int) $order[($pos + $i) % count($order)];
            if (!empty($players[$cand]['active']) && !in_array($cand, $pub['solved'], true)) {
                $next = $cand;
                break;
            }
        }
        if ($next === null || $now >= (int) $pub['round_end']) {
            $this->reveal($room, $round, $pub, $players, $now);
            return;
        }
        $pub['turn']++;
        $pub['current'] = $next;
        $this->repo()->savePublic((int) $round['id'], $pub);
        $this->phase($room, $round, 'ask', max(1, min($this->int($room, 'ask_time', 45), (int) $pub['round_end'] - $now)), $now);
    }

    private function reveal(array $room, array $round, array $pub, array $players, int $now): void
    {
        $number = (int) $round['number'];
        $items = [];
        foreach ($pub['order'] as $pid) {
            $rp = $players[(int) $pid] ?? null;
            if ($rp === null) {
                continue;
            }
            $items[] = ['nickname' => (string) $rp['nickname'], 'identity' => (string) $rp['secret']['name'], 'solved' => in_array((int) $pid, $pub['solved'], true)];
        }
        $pub['result'] = ['items' => $items];
        $this->repo()->savePublic((int) $round['id'], $pub);
        $this->ctx->event($room, $number, 'Quem Sou Eu? — rodada ' . $number, sprintf('%d de %d descobriram quem eram.', count($pub['solved']), count($items)), "r$number");
        $this->phase($room, $round, 'reveal', $this->int($room, 'reveal_time', 20), $now);
    }

    protected function handle(string $op, array $room, array $round, array $player, Input $in): array
    {
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        $pub = $round['public'];
        $turn = (int) $pub['turn'];
        $rp = $this->requireParticipant($round, $player);
        $now = time();
        switch ($op) {
            case 'ask':
            case 'guess':
                $this->requirePhase($round, 'ask', 'Agora não é hora de perguntar.');
                if ($pid !== (int) $pub['current']) {
                    throw new ApiException('Aguarde a sua vez.', 403, 'not_your_turn');
                }
                if ($op === 'ask') {
                    $text = Text::limited($in->string('text', 1500, 'pergunta'), self::Q_MIN, self::Q_MAX, 'A pergunta');
                    $this->repo()->addEntry($roundId, $pid, 'q' . $turn, $text, [], $now);
                    $pub['log'][] = ['type' => 'question', 'turn' => $turn, 'nickname' => (string) $player['nickname'], 'text' => $text, 'answers' => null];
                    $this->repo()->savePublic($roundId, $pub);
                    $this->phase($room, $round, 'answer', max(1, min($this->int($room, 'answer_time', 20), (int) $pub['round_end'] - $now)), $now);
                    return ['asked' => $text];
                }
                $guess = Text::limited($in->string('text', 600, 'palpite'), 1, 60, 'O palpite');
                $mine = $this->ctx->db()->fetchAll(
                    "SELECT body FROM game_entries WHERE round_id = ? AND player_id = ? AND kind LIKE 'g%'",
                    [$roundId, $pid]
                );
                if (count($mine) >= $this->int($room, 'max_guesses', 3)) {
                    throw new ApiException('Seus palpites acabaram nesta rodada. Continue perguntando!', 409, 'no_guesses');
                }
                foreach ($mine as $m) {
                    if (Text::fold((string) $m['body']) === Text::fold($guess)) {
                        throw new ApiException('Você já tentou esse palpite.', 409, 'repeated');
                    }
                }
                $this->repo()->addEntry($roundId, $pid, 'g' . (count($mine) + 1), $guess, [], $now);
                $correct = self::matches($guess, $rp['secret']);
                $number = (int) $round['number'];
                if ($correct) {
                    $pub['solved'][] = $pid;
                    $this->ctx->award($room, $number, $pid, $this->int($room, 'points_solve', 5), 'Descobriu quem era', "r$number:solve");
                    $asked = (int) $this->ctx->db()->fetchValue(
                        "SELECT COUNT(*) FROM game_entries WHERE round_id = ? AND player_id = ? AND kind LIKE 'q%'",
                        [$roundId, $pid]
                    );
                    if ($asked <= self::FAST) {
                        $this->ctx->award($room, $number, $pid, $this->int($room, 'bonus_fast', 2), 'Acertou rapidinho', "r$number:fast");
                    }
                }
                $pub['log'][] = [
                    'type' => 'guess', 'turn' => $turn, 'nickname' => (string) $player['nickname'], 'text' => $guess,
                    'correct' => $correct, 'answers' => [],
                ];
                $this->repo()->savePublic($roundId, $pub);
                $this->nextTurn($room, $round, $pub, $this->repo()->players($roundId), $now);
                return ['correct' => $correct, 'identity' => $correct ? (string) $rp['secret']['name'] : null];

            case 'answer':
                $this->requirePhase($round, 'answer', 'Não há pergunta para responder agora.');
                if ($pid === (int) $pub['current']) {
                    throw new ApiException('Você não responde à própria pergunta.', 403, 'own_question');
                }
                $value = $in->string('value', 5, 'resposta');
                if (!in_array($value, self::ANSWERS, true)) {
                    throw new ApiException('Responda sim, não ou não sei.', 422, 'invalid_input');
                }
                $this->repo()->castVote($roundId, $pid, 'a' . $turn, $value, $now);
                $this->ctx->bump((int) $room['id'], $now);
                return ['answered' => true];
        }
        throw new ApiException('Ação desconhecida.', 400, 'invalid_op');
    }

    protected function roundView(array $room, array $round, array $player, array $roundPlayers): array
    {
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        $phase = (string) $round['phase'];
        $pub = $round['public'];
        $solved = array_map('intval', $pub['solved']);
        $iSolved = in_array($pid, $solved, true);
        $people = [];
        $progress = [];
        $answered = $phase === 'answer'
            ? array_map('intval', array_column($this->answers($roundId, (int) $pub['turn']), 'voter_id'))
            : [];
        foreach ($pub['order'] as $id) {
            $id = (int) $id;
            $rp = $roundPlayers[$id] ?? null;
            if ($rp === null) {
                continue;
            }
            // Nunca enviar ao jogador a própria identidade antes de ele acertar ou da revelação.
            $show = $id !== $pid || $iSolved || $phase === 'reveal';
            $people[] = [
                'id' => $id,
                'nickname' => (string) $rp['nickname'],
                'identity' => $show ? (string) $rp['secret']['name'] : null,
                'me' => $id === $pid,
                'solved' => in_array($id, $solved, true),
                'current' => $id === (int) $pub['current'] && $phase !== 'reveal',
            ];
            if ($phase === 'answer' && $id !== (int) $pub['current']) {
                $progress[$id] = in_array($id, $answered, true) ? 'submitted' : 'pending';
            }
        }
        $guesses = (int) $this->ctx->db()->fetchValue(
            "SELECT COUNT(*) FROM game_entries WHERE round_id = ? AND player_id = ? AND kind LIKE 'g%'",
            [$roundId, $pid]
        );
        $last = end($pub['log']) ?: null;
        $mine = $roundPlayers[$pid] ?? null;
        return [
            'people' => $people,
            'my_category' => $mine !== null ? (string) $mine['secret']['category'] : null,
            'my_turn' => $phase === 'ask' && (int) $pub['current'] === $pid,
            'current_question' => $phase === 'answer' && $last && $last['type'] === 'question' ? $last['text'] : null,
            'can_answer' => $phase === 'answer' && (int) $pub['current'] !== $pid && $mine !== null,
            'my_answer' => $phase === 'answer' ? ($this->repo()->votesOf($roundId, $pid)['a' . (int) $pub['turn']] ?? null) : null,
            'solved_me' => $iSolved,
            'guesses_left' => max(0, $this->int($room, 'max_guesses', 3) - $guesses),
            'round_end' => (int) $pub['round_end'] * 1000,
            'log' => array_slice(array_reverse($pub['log']), 0, 40),
            'q_max' => self::Q_MAX,
            'result' => $phase === 'reveal' ? ($pub['result'] ?? null) : null,
            'progress' => $progress,
        ];
    }
}
