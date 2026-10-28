<?php
declare(strict_types=1);

namespace Games\SecretMission;

use App\Core\ApiException;
use App\Core\Input;
use App\Core\Text;
use App\Platform\RoundGame;

/**
 * "Missão Secreta"
 *
 * Rodada: briefing ─▶ chat ─▶ guessing (opcional) ─▶ evaluation (se houver missão subjetiva) ─▶ reveal
 *
 * - Missões distintas por jogador (sem repetir enquanto houver alternativas), guardadas em
 *   game_round_players.secret. A visão de cada jogador lê SOMENTE o próprio segredo.
 * - Bate-papo: mensagens em game_entries (kind "m<n>", n = nº da mensagem do jogador).
 * - Verificação automática no fim do chat, só com mensagens de OUTROS jogadores (ou do alvo).
 * - Missões subjetivas ('vote'): cumpridas se a maioria dos votos dos outros for "sim"; sem votos = não cumprida.
 * - Palpites: cédula "guess:<id do jogador>", um por alvo; avaliações: cédula "eval:<id>". Votos únicos (UNIQUE).
 */
final class Game extends RoundGame
{
    private const BRIEFING = 15;
    private const MSG_MAX = 200;
    private const MSG_LIMIT = 60;
    private const NUMBERS = ['zero', 'um', 'uma', 'dois', 'duas', 'tres', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove', 'dez', 'cem', 'mil'];

    private static function content(): array
    {
        return require dirname(__DIR__) . '/content/missions.php';
    }

    protected function skippablePhases(): array
    {
        return ['briefing', 'chat', 'reveal'];
    }

    protected function planRound(array $room, array $players, int $number, int $now): array
    {
        $c = self::content();
        $ids = array_map('intval', array_column($players, 'id'));
        $names = array_column($players, 'nickname', 'id');
        // Missões não repetem na rodada; tipos 'target' e 'vote' aparecem no máximo uma vez cada se possível.
        $pool = self::shuffle($c['missions']);
        $picked = [];
        $usedTypes = [];
        foreach ([true, false] as $varied) {
            foreach ($pool as $m) {
                if (count($picked) >= count($ids)) {
                    break 2;
                }
                if (isset($picked[$m['key']]) || ($varied && $m['type'] !== 'word' && isset($usedTypes[$m['type']]))) {
                    continue;
                }
                $picked[$m['key']] = $m;
                $usedTypes[$m['type']] = true;
            }
        }
        $picked = self::shuffle(array_values($picked));
        $assign = [];
        foreach ($ids as $i => $pid) {
            $m = $picked[$i % count($picked)];
            $others = array_values(array_diff($ids, [$pid]));
            $target = $others[random_int(0, count($others) - 1)];
            $m['target'] = strpos($m['type'], 'target_') === 0 ? $target : null;
            $m['text'] = str_replace('{alvo}', (string) $names[$target], $m['text']);
            $assign[$pid] = ['role' => 'agent', 'secret' => $m];
        }
        $topics = $c['topics'];
        return [
            'phase' => 'briefing',
            'seconds' => self::BRIEFING,
            'prompt_key' => 'r' . $number . ':' . random_int(1, 999999),
            'public' => ['topic' => $topics[random_int(0, count($topics) - 1)]],
            'players' => $assign,
        ];
    }

    /** @return array<int, array{id:int, player_id:int, nickname:string, body:string}> */
    private function messages(int $roundId): array
    {
        return $this->ctx->db()->fetchAll(
            "SELECT e.id, e.player_id, p.nickname, e.body FROM game_entries e JOIN players p ON p.id = e.player_id
              WHERE e.round_id = ? AND e.kind LIKE 'm%' ORDER BY e.id",
            [$roundId]
        );
    }

    /** Verificação automática de uma missão contra as mensagens do bate-papo. null = depende de votação. */
    public static function autoCheck(array $mission, int $owner, string $ownerName, array $messages, array $lists): ?bool
    {
        if ($mission['type'] === 'vote') {
            return null;
        }
        foreach ($messages as $msg) {
            $author = (int) $msg['player_id'];
            $body = (string) $msg['body'];
            $tokens = array_map([Text::class, 'fold'], preg_split('/[^\p{L}\p{N}]+/u', $body) ?: []);
            switch ($mission['type']) {
                case 'word':
                    if ($author !== $owner && strpos(Text::fold($body), Text::fold($mission['word'])) !== false) {
                        return true;
                    }
                    break;
                case 'list':
                    if ($author !== $owner && array_intersect($tokens, $lists[$mission['list']] ?? [])) {
                        return true;
                    }
                    break;
                case 'target_question':
                    if ($author === (int) $mission['target'] && strpos($body, '?') !== false) {
                        return true;
                    }
                    break;
                case 'target_name':
                    if ($author === (int) $mission['target'] && strpos(Text::fold($body), Text::fold($ownerName)) !== false) {
                        return true;
                    }
                    break;
                case 'target_number':
                    if ($author === (int) $mission['target'] && (preg_match('/\d/', $body) || array_intersect($tokens, self::NUMBERS))) {
                        return true;
                    }
                    break;
            }
        }
        return false;
    }

    protected function step(array $room, array $round, int $now): bool
    {
        $roundId = (int) $round['id'];
        $players = $this->repo()->players($roundId);
        $active = self::activeIds($players);
        switch ($round['phase']) {
            case 'briefing':
                if (!$this->expired($round, $now)) {
                    return false;
                }
                $this->phase($room, $round, 'chat', $this->int($room, 'chat_time', 180), $now);
                return true;
            case 'chat':
                if (!$this->expired($round, $now)) {
                    return false;
                }
                $this->afterChat($room, $round, $players, $now);
                return true;
            case 'guessing':
                $pending = false;
                $votes = $this->repo()->votes($roundId, 'guess:');
                $count = array_count_values(array_map('intval', array_column($votes, 'voter_id')));
                foreach ($active as $pid) {
                    if (($count[$pid] ?? 0) < count($players) - 1) {
                        $pending = true;
                    }
                }
                if (!$this->expired($round, $now) && $pending) {
                    return false;
                }
                $this->toEvaluation($room, $round, $players, $now);
                return true;
            case 'evaluation':
                $subjective = $this->subjectiveOwners($players);
                $votes = $this->repo()->votes($roundId, 'eval:');
                $count = array_count_values(array_map('intval', array_column($votes, 'voter_id')));
                $pending = false;
                foreach ($active as $pid) {
                    $need = count(array_diff($subjective, [$pid]));
                    if (($count[$pid] ?? 0) < $need) {
                        $pending = true;
                    }
                }
                if (!$this->expired($round, $now) && $pending) {
                    return false;
                }
                $this->reveal($room, $round, $players, $now);
                return true;
            case 'reveal':
                if (!$this->expired($round, $now)) {
                    return false;
                }
                $this->finishRound($room, $round, $now);
                return true;
        }
        return false;
    }

    private function subjectiveOwners(array $players): array
    {
        $out = [];
        foreach ($players as $pid => $rp) {
            if (($rp['secret']['type'] ?? '') === 'vote') {
                $out[] = (int) $pid;
            }
        }
        return $out;
    }

    /** Fim do chat: verifica as missões automáticas e abre os palpites (ou a avaliação). */
    private function afterChat(array $room, array $round, array $players, int $now): void
    {
        $lists = self::content()['lists'];
        $messages = $this->messages((int) $round['id']);
        $auto = [];
        foreach ($players as $pid => $rp) {
            $auto[(int) $pid] = self::autoCheck($rp['secret'], (int) $pid, (string) $rp['nickname'], $messages, $lists);
        }
        $secret = $round['secret'];
        $secret['auto'] = $auto;
        if ($this->ctx->setting($room, 'guessing', true) && count($players) >= 2) {
            // Opções de palpite: descrições genéricas (sem nomes), em ordem embaralhada, com códigos aleatórios.
            $options = [];
            $map = [];
            foreach (self::shuffle(array_keys($players)) as $pid) {
                $code = bin2hex(random_bytes(4));
                $options[] = ['id' => $code, 'label' => (string) $players[$pid]['secret']['label']];
                $map[$code] = (int) $pid;
            }
            $secret['options'] = $map;
            $this->repo()->saveSecret((int) $round['id'], $secret);
            $public = $round['public'];
            $public['options'] = $options;
            $this->repo()->savePublic((int) $round['id'], $public);
            $this->phase($room, $round, 'guessing', $this->int($room, 'guess_time', 60), $now);
            return;
        }
        $this->repo()->saveSecret((int) $round['id'], $secret);
        $round['secret'] = $secret;
        $this->toEvaluation($room, $round, $players, $now);
    }

    private function toEvaluation(array $room, array $round, array $players, int $now): void
    {
        if ($this->subjectiveOwners($players)) {
            $this->phase($room, $round, 'evaluation', $this->int($room, 'eval_time', 30), $now);
            return;
        }
        $this->reveal($room, $this->repo()->find((int) $round['id']), $players, $now);
    }

    private function reveal(array $room, array $round, array $players, int $now): void
    {
        $roundId = (int) $round['id'];
        $number = (int) $round['number'];
        $secret = $round['secret'];
        $auto = $secret['auto'] ?? [];
        $optionOwner = $secret['options'] ?? [];
        $evals = [];
        foreach ($this->repo()->votes($roundId, 'eval:') as $v) {
            $owner = (int) substr((string) $v['ballot'], 5);
            $evals[$owner][$v['target']] = ($evals[$owner][$v['target']] ?? 0) + 1;
        }
        $guessedBy = [];
        foreach ($this->repo()->votes($roundId, 'guess:') as $v) {
            $target = (int) substr((string) $v['ballot'], 6);
            if (($optionOwner[$v['target']] ?? null) === $target) {
                $guessedBy[$target][] = (int) $v['voter_id'];
                $this->ctx->award($room, $number, (int) $v['voter_id'], $this->int($room, 'points_guess', 1), 'Adivinhou uma missão', "r$number:guess:$target");
            }
        }
        $items = [];
        $done = 0;
        foreach ($players as $pid => $rp) {
            $pid = (int) $pid;
            $m = $rp['secret'];
            if ($m['type'] === 'vote') {
                $yes = $evals[$pid]['yes'] ?? 0;
                $no = $evals[$pid]['no'] ?? 0;
                $completed = $yes > $no;
                $how = sprintf('votação: %d sim × %d não', $yes, $no);
            } else {
                $completed = !empty($auto[$pid]);
                $how = 'verificada no chat';
            }
            $discovered = !empty($guessedBy[$pid]);
            if ($completed) {
                $done++;
                $this->ctx->award($room, $number, $pid, $this->int($room, 'points_mission', 3), 'Cumpriu a missão secreta', "r$number:mission");
                if (!$discovered && ($optionOwner || !$this->ctx->setting($room, 'guessing', true))) {
                    $this->ctx->award($room, $number, $pid, $this->int($room, 'points_stealth', 1), 'Cumpriu sem ser descoberto', "r$number:stealth");
                }
            }
            $items[] = [
                'nickname' => (string) $rp['nickname'],
                'mission' => (string) $m['text'],
                'completed' => $completed,
                'how' => $how,
                'guessed_by' => self::names($players, $guessedBy[$pid] ?? []),
            ];
        }
        $public = $round['public'];
        $public['result'] = ['items' => $items];
        $this->repo()->savePublic($roundId, $public);
        $this->ctx->event($room, $number, 'Missões da rodada ' . $number, sprintf('%d de %d missões cumpridas.', $done, count($items)), "r$number");
        $this->phase($room, $round, 'reveal', $this->int($room, 'reveal_time', 25), $now);
    }

    protected function handle(string $op, array $room, array $round, array $player, Input $in): array
    {
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        $now = time();
        $this->requireParticipant($round, $player);
        switch ($op) {
            case 'say':
                $this->requirePhase($round, 'chat', 'O bate-papo não está aberto.');
                $text = Text::limited($in->string('text', 2000, 'mensagem'), 1, self::MSG_MAX, 'A mensagem');
                $n = (int) $this->ctx->db()->fetchValue(
                    "SELECT COUNT(*) FROM game_entries WHERE round_id = ? AND player_id = ? AND kind LIKE 'm%'",
                    [$roundId, $pid]
                );
                if ($n >= self::MSG_LIMIT) {
                    throw new ApiException('Você atingiu o limite de mensagens desta rodada.', 429, 'too_many_messages');
                }
                $this->repo()->addEntry($roundId, $pid, 'm' . ($n + 1), $text, [], $now);
                $this->ctx->bump((int) $room['id'], $now);
                return ['sent' => true];
            case 'guess':
                $this->requirePhase($round, 'guessing', 'Os palpites não estão abertos.');
                $target = $in->int('player', 1, PHP_INT_MAX >> 1, null, 'jogador');
                $option = $in->string('option', 20, 'missão');
                $players = $this->repo()->players($roundId);
                if ($target === $pid || !isset($players[$target])) {
                    throw new ApiException('Escolha outro jogador desta rodada.', 422, 'invalid_target');
                }
                if (!isset($round['secret']['options'][$option])) {
                    throw new ApiException('Escolha uma das missões da lista.', 422, 'invalid_target');
                }
                $this->repo()->castVote($roundId, $pid, 'guess:' . $target, $option, $now);
                $this->ctx->bump((int) $room['id'], $now);
                return ['guessed' => true];
            case 'evaluate':
                $this->requirePhase($round, 'evaluation', 'A avaliação não está aberta.');
                $owner = $in->int('player', 1, PHP_INT_MAX >> 1, null, 'jogador');
                if ($owner === $pid || !in_array($owner, $this->subjectiveOwners($this->repo()->players($roundId)), true)) {
                    throw new ApiException('Você não pode avaliar esta missão.', 422, 'invalid_target');
                }
                $this->repo()->castVote($roundId, $pid, 'eval:' . $owner, $in->bool('done') ? 'yes' : 'no', $now);
                $this->ctx->bump((int) $room['id'], $now);
                return ['evaluated' => true];
        }
        throw new ApiException('Ação desconhecida.', 400, 'invalid_op');
    }

    protected function roundView(array $room, array $round, array $player, array $roundPlayers): array
    {
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        $phase = (string) $round['phase'];
        $mine = $roundPlayers[$pid] ?? null;
        $view = [
            'topic' => (string) $round['public']['topic'],
            // Somente a própria missão — nunca a dos outros.
            'mission' => $mine !== null ? (string) $mine['secret']['text'] : null,
            'msg_max' => self::MSG_MAX,
            'messages' => [],
            'progress' => [],
        ];
        if ($phase !== 'briefing') {
            foreach (array_slice($this->messages($roundId), -120) as $m) {
                $view['messages'][] = ['nickname' => (string) $m['nickname'], 'text' => (string) $m['body'], 'me' => (int) $m['player_id'] === $pid];
            }
        }
        $myVotes = $this->repo()->votesOf($roundId, $pid);
        if ($phase === 'guessing') {
            $view['options'] = $round['public']['options'] ?? [];
            $view['targets'] = [];
            foreach ($roundPlayers as $id => $rp) {
                if ((int) $id !== $pid) {
                    $view['targets'][] = ['id' => (int) $id, 'nickname' => (string) $rp['nickname'], 'my_guess' => $myVotes['guess:' . $id] ?? null];
                }
            }
            $count = array_count_values(array_map('intval', array_column($this->repo()->votes($roundId, 'guess:'), 'voter_id')));
            foreach ($roundPlayers as $id => $rp) {
                $view['progress'][(int) $id] = ($count[(int) $id] ?? 0) >= count($roundPlayers) - 1 ? 'submitted' : 'pending';
            }
        }
        if ($phase === 'evaluation') {
            // Só as missões subjetivas, que precisam do julgamento do grupo.
            $view['evaluations'] = [];
            foreach ($this->subjectiveOwners($roundPlayers) as $owner) {
                if ($owner !== $pid) {
                    $view['evaluations'][] = [
                        'player' => $owner,
                        'nickname' => (string) $roundPlayers[$owner]['nickname'],
                        'mission' => (string) $roundPlayers[$owner]['secret']['text'],
                        'my_vote' => $myVotes['eval:' . $owner] ?? null,
                    ];
                }
            }
        }
        if ($phase === 'reveal') {
            $view['result'] = $round['public']['result'] ?? null;
        }
        return $view;
    }
}
