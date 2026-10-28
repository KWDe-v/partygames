<?php
declare(strict_types=1);

namespace Games\Infiltrator;

use App\Core\ApiException;
use App\Core\Input;
use App\Core\Text;
use App\Platform\RoundGame;

/**
 * "O Infiltrado"
 *
 * Rodada: roles ─▶ hints ─▶ discussion ─▶ voting ─▶ reveal ─▶ próxima rodada
 *
 * Papéis e palavras são sorteados no servidor e guardados por jogador em game_round_players.secret;
 * cada jogador só recebe o próprio segredo. A palavra e os infiltrados só são enviados na revelação.
 *
 * Quantidade de infiltrados: a configurada, limitada a ⌊(jogadores − 1) / 2⌋ (mínimo 1), para que
 * sempre haja mais membros do grupo que infiltrados — nunca igual ou maior que o total de jogadores.
 *
 * Regra de vitória (padrão):
 *  - Os k jogadores mais votados (k = nº de infiltrados) são os "acusados".
 *  - O GRUPO vence se os acusados forem exatamente os infiltrados.
 *  - Empate na fronteira dos k primeiros, nenhum voto ou acusação errada ⇒ resultado inconclusivo
 *    ou incorreto ⇒ os INFILTRADOS vencem (não foram identificados).
 * Pontuação: vencedores recebem points_group (cada membro do grupo) ou points_infiltrator (cada
 * infiltrado); cada membro do grupo que votou em um infiltrado recebe points_vote.
 */
final class Game extends RoundGame
{
    private const ROLES_SECONDS = 10;
    private const HINT_MAX = 40;

    protected function skippablePhases(): array
    {
        return ['roles', 'discussion', 'reveal'];
    }

    /** Quantos infiltrados com $n jogadores. */
    public static function infiltratorCount(int $setting, int $n): int
    {
        return max(1, min($setting, $n - 1, intdiv($n - 1, 2)));
    }

    protected function planRound(array $room, array $players, int $number, int $now): array
    {
        $pool = [];
        $chosen = (array) $this->ctx->setting($room, 'categories', []);
        foreach (require dirname(__DIR__) . '/content/words.php' as $key => $cat) {
            if (in_array($key, $chosen, true)) {
                foreach ($cat['items'] as $i => $pair) {
                    $pool[] = ['key' => "$key:$i", 'word' => $pair[0], 'similar' => $pair[1], 'category' => $cat['label']];
                }
            }
        }
        $pick = $this->pickPrompt($room, $pool);
        $mode = (string) $this->ctx->setting($room, 'mode', 'classic');
        $ids = array_map('intval', array_column($players, 'id'));
        $k = self::infiltratorCount($this->int($room, 'infiltrators', 1), count($ids));
        $infiltrators = array_slice(self::shuffle($ids), 0, $k);

        $assign = [];
        foreach ($ids as $pid) {
            $isInf = in_array($pid, $infiltrators, true);
            $assign[$pid] = [
                'role' => $isInf ? 'infiltrator' : 'group',
                'secret' => $isInf
                    ? ['word' => $mode === 'similar' ? $pick['similar'] : null]
                    : ['word' => $pick['word']],
            ];
        }
        return [
            'phase' => 'roles',
            'seconds' => self::ROLES_SECONDS,
            'prompt_key' => $pick['key'],
            'public' => ['category' => $pick['category'], 'mode' => $mode, 'infiltrators' => $k],
            'secret' => ['word' => $pick['word'], 'similar' => $pick['similar'], 'infiltrators' => $infiltrators],
            'players' => $assign,
        ];
    }

    protected function step(array $room, array $round, int $now): bool
    {
        $roundId = (int) $round['id'];
        switch ($round['phase']) {
            case 'roles':
                if (!$this->expired($round, $now)) {
                    return false;
                }
                $this->phase($room, $round, 'hints', $this->int($room, 'hint_time', 45), $now);
                return true;
            case 'hints':
                $players = $this->repo()->players($roundId);
                if (!$this->expired($round, $now) && !$this->allSubmitted($roundId, self::activeIds($players), 'hint')) {
                    return false;
                }
                $discussion = $this->int($room, 'discussion_time', 60);
                if ($discussion > 0) {
                    $this->phase($room, $round, 'discussion', $discussion, $now);
                } else {
                    $this->phase($room, $round, 'voting', $this->int($room, 'vote_time', 40), $now);
                }
                return true;
            case 'discussion':
                if (!$this->expired($round, $now)) {
                    return false;
                }
                $this->phase($room, $round, 'voting', $this->int($room, 'vote_time', 40), $now);
                return true;
            case 'voting':
                $players = $this->repo()->players($roundId);
                $voted = array_map('intval', array_column($this->repo()->votes($roundId, 'main'), 'voter_id'));
                if (!$this->expired($round, $now) && array_diff(self::activeIds($players), $voted)) {
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

    /** Aplica a regra de vitória documentada acima e distribui os pontos. */
    public static function resolve(array $counts, array $infiltrators): array
    {
        arsort($counts);
        $k = count($infiltrators);
        $values = array_values($counts);
        $ids = array_map('intval', array_keys($counts));
        $total = array_sum($values);
        if ($total === 0) {
            return ['outcome' => 'infiltrators', 'accused' => [], 'reason' => 'Ninguém votou: os infiltrados escaparam.'];
        }
        if (($values[$k - 1] ?? 0) === 0 || (isset($values[$k]) && $values[$k] === $values[$k - 1])) {
            return ['outcome' => 'infiltrators', 'accused' => [], 'reason' => 'Empate na votação: ninguém foi desmascarado.'];
        }
        $accused = array_slice($ids, 0, $k);
        sort($accused);
        $inf = array_map('intval', $infiltrators);
        sort($inf);
        if ($accused === $inf) {
            return ['outcome' => 'group', 'accused' => $accused, 'reason' => $k === 1 ? 'O infiltrado foi desmascarado!' : 'Todos os infiltrados foram desmascarados!'];
        }
        return ['outcome' => 'infiltrators', 'accused' => $accused, 'reason' => 'O grupo acusou a pessoa errada.'];
    }

    private function reveal(array $room, array $round, array $players, int $now): void
    {
        $roundId = (int) $round['id'];
        $number = (int) $round['number'];
        $secret = $round['secret'];
        $infiltrators = array_map('intval', $secret['infiltrators']);
        $votes = $this->repo()->votes($roundId, 'main');
        $t = self::tally($votes, array_keys($players));
        $result = self::resolve($t['counts'], $infiltrators);

        $group = array_values(array_diff(array_map('intval', array_keys($players)), $infiltrators));
        if ($result['outcome'] === 'group') {
            foreach ($group as $pid) {
                $this->ctx->award($room, $number, $pid, $this->int($room, 'points_group', 2), 'Grupo venceu a rodada ' . $number, "r$number:win");
            }
        } else {
            foreach ($infiltrators as $pid) {
                $this->ctx->award($room, $number, $pid, $this->int($room, 'points_infiltrator', 3), 'Infiltrado venceu a rodada ' . $number, "r$number:win");
            }
        }
        foreach ($votes as $v) {
            $voter = (int) $v['voter_id'];
            if (in_array($voter, $group, true) && in_array((int) $v['target'], $infiltrators, true)) {
                $this->ctx->award($room, $number, $voter, $this->int($room, 'points_vote', 1), 'Votou no infiltrado', "r$number:vote");
            }
        }

        $counts = [];
        foreach ($t['counts'] as $pid => $n) {
            if (isset($players[(int) $pid])) {
                $counts[] = ['player_id' => (int) $pid, 'nickname' => (string) $players[(int) $pid]['nickname'], 'votes' => $n];
            }
        }
        $public = $round['public'];
        $public['result'] = [
            'word' => $secret['word'],
            'similar' => $public['mode'] === 'similar' ? $secret['similar'] : null,
            'infiltrators' => array_map(static function (int $id) use ($players): array {
                return ['id' => $id, 'nickname' => (string) ($players[$id]['nickname'] ?? '?')];
            }, $infiltrators),
            'accused' => $result['accused'],
            'outcome' => $result['outcome'],
            'reason' => $result['reason'],
            'counts' => $counts,
            'total_votes' => $t['total'],
        ];
        $this->repo()->savePublic($roundId, $public);
        $this->ctx->event(
            $room,
            $number,
            'Palavra: ' . $secret['word'],
            sprintf('%s %s: %s.', $result['outcome'] === 'group' ? 'Vitória do grupo!' : 'Vitória dos infiltrados!', count($infiltrators) > 1 ? 'Infiltrados' : 'Infiltrado', self::names($players, $infiltrators)),
            "r$number"
        );
        $this->phase($room, $round, 'reveal', $this->int($room, 'reveal_time', 15), $now);
    }

    protected function handle(string $op, array $room, array $round, array $player, Input $in): array
    {
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        if ($op === 'hint') {
            $this->requirePhase($round, 'hints', 'A etapa de dicas não está aberta.');
            $rp = $this->requireParticipant($round, $player);
            $hint = Text::limited($in->string('text', 400, 'dica'), 2, self::HINT_MAX, 'A dica');
            $word = (string) ($rp['secret']['word'] ?? '');
            if ($word !== '' && strpos(self::fold($hint), self::fold($word)) !== false) {
                throw new ApiException('A dica não pode conter a palavra secreta.', 422, 'hint_has_word');
            }
            $this->repo()->addEntry($roundId, $pid, 'hint', $hint, [], time());
            $this->ctx->bump((int) $room['id'], time());
            return ['hint' => $hint];
        }
        if ($op === 'vote') {
            $this->requirePhase($round, 'voting', 'A votação não está aberta.');
            $this->requireParticipant($round, $player);
            $target = $in->int('target', 1, PHP_INT_MAX >> 1, null, 'jogador');
            $players = $this->repo()->players($roundId);
            if ($target === $pid) {
                throw new ApiException('Você não pode votar em si mesmo.', 422, 'self_vote');
            }
            if (!isset($players[$target])) {
                throw new ApiException('Escolha um jogador desta rodada.', 422, 'invalid_target');
            }
            $this->repo()->castVote($roundId, $pid, 'main', (string) $target, time());
            $this->ctx->bump((int) $room['id'], time());
            return ['voted' => true];
        }
        throw new ApiException('Ação desconhecida.', 400, 'invalid_op');
    }

    /** Minúsculas sem acentos, para comparar a dica com a palavra secreta. */
    private static function fold(string $s): string
    {
        $s = strtr($s, [
            'Á' => 'a', 'À' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Ä' => 'a', 'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'É' => 'e', 'Ê' => 'e', 'È' => 'e', 'é' => 'e', 'ê' => 'e', 'è' => 'e', 'Í' => 'i', 'í' => 'i', 'Ó' => 'o', 'Ô' => 'o',
            'Õ' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'ú' => 'u', 'ü' => 'u', 'Ç' => 'c', 'ç' => 'c',
        ]);
        return (string) preg_replace('/[^a-z0-9]/', '', strtolower($s));
    }

    protected function roundView(array $room, array $round, array $player, array $roundPlayers): array
    {
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        $phase = (string) $round['phase'];
        $mine = $roundPlayers[$pid] ?? null;
        $mode = (string) $round['public']['mode'];

        // Segredo individual: só o do próprio jogador. No modo "palavra parecida" ninguém sabe o próprio papel.
        $secret = null;
        if ($mine !== null) {
            $isInf = $mine['role'] === 'infiltrator';
            $secret = [
                'word' => $mine['secret']['word'] ?? null,
                'role' => $mode === 'classic' ? ($isInf ? 'infiltrator' : 'group') : 'unknown',
            ];
        }

        $hints = $this->repo()->entries($roundId, 'hint');
        $hinted = array_map('intval', array_column($hints, 'player_id'));
        $voters = array_map('intval', array_column($this->repo()->votes($roundId, 'main'), 'voter_id'));
        $progress = [];
        $candidates = [];
        foreach ($roundPlayers as $id => $rp) {
            $candidates[] = ['id' => (int) $id, 'nickname' => (string) $rp['nickname'], 'me' => (int) $id === $pid];
            if ($phase === 'hints') {
                $progress[(int) $id] = in_array((int) $id, $hinted, true) ? 'submitted' : 'pending';
            } elseif ($phase === 'voting') {
                $progress[(int) $id] = in_array((int) $id, $voters, true) ? 'submitted' : 'pending';
            }
        }

        $view = [
            'category' => (string) $round['public']['category'],
            'mode' => $mode,
            'infiltrators' => (int) $round['public']['infiltrators'],
            'secret' => $secret,
            'candidates' => $candidates,
            'my_hint' => null,
            'my_vote' => null,
            'hints_done' => count($hints),
            'votes_cast' => count($voters),
            'hint_max' => self::HINT_MAX,
            'progress' => $progress,
        ];
        foreach ($hints as $h) {
            if ((int) $h['player_id'] === $pid) {
                $view['my_hint'] = (string) $h['body'];
            }
        }
        // Dicas são públicas (com autor) a partir da discussão — é assim que o grupo investiga.
        if (in_array($phase, ['discussion', 'voting', 'reveal'], true)) {
            $view['hints'] = array_map(static function (array $h): array {
                return ['player_id' => (int) $h['player_id'], 'nickname' => (string) $h['nickname'], 'text' => (string) $h['body']];
            }, $hints);
        }
        $my = $this->repo()->votesOf($roundId, $pid)['main'] ?? null;
        $view['my_vote'] = $my !== null ? (int) $my : null;
        if ($phase === 'reveal') {
            $view['result'] = $round['public']['result'] ?? null;
        }
        return $view;
    }
}
