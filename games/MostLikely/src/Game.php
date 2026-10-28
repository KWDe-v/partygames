<?php
declare(strict_types=1);

namespace Games\MostLikely;

use App\Core\ApiException;
use App\Core\Input;
use App\Platform\RoundGame;

/**
 * "Quem é Mais Provável?"
 *
 * Rodada: voting ──(todos votaram ou prazo)──▶ reveal ──▶ próxima rodada
 *
 * - Voto secreto em outro participante da rodada; um por rodada (UNIQUE no banco), sem alteração.
 * - Quem não votar fica sem voto: nenhum voto é inventado.
 * - Revelação: só a contagem por jogador (nunca quem votou em quem). Empatados no topo são destacados.
 * - Pontuação (opcional): os mais votados — todos, em caso de empate — ganham 'points'.
 *   Se ninguém votar, ninguém pontua.
 */
final class Game extends RoundGame
{
    public function normalizeSettings(array $s): array
    {
        if (!$s['categories'] && !$s['custom']) {
            throw new ApiException('Escolha pelo menos uma categoria ou escreva perguntas personalizadas.', 422, 'invalid_input');
        }
        return $s;
    }

    protected function planRound(array $room, array $players, int $number, int $now): array
    {
        $prompt = $this->pickPrompt($room, $this->pool($room));
        return [
            'phase' => 'voting',
            'seconds' => $this->int($room, 'vote_time', 30),
            'prompt_key' => $prompt['key'],
            'public' => ['question' => $prompt['text'], 'category' => $prompt['category']],
        ];
    }

    /** Perguntas das categorias escolhidas + personalizadas, já no formato completo. */
    private function pool(array $room): array
    {
        $chosen = (array) $this->ctx->setting($room, 'categories', []);
        $pool = [];
        foreach (require dirname(__DIR__) . '/content/questions.php' as $key => $cat) {
            if (!in_array($key, $chosen, true)) {
                continue;
            }
            foreach ($cat['items'] as $i => $text) {
                $pool[] = ['key' => "$key:$i", 'text' => self::format($text), 'category' => $cat['label']];
            }
        }
        foreach ((array) $this->ctx->setting($room, 'custom', []) as $i => $text) {
            $pool[] = ['key' => 'custom:' . md5($text), 'text' => self::format($text), 'category' => 'Personalizada'];
        }
        return $pool;
    }

    /** Aceita "dormir no ônibus" ou "Quem é mais provável de dormir no ônibus?". */
    private static function format(string $text): string
    {
        $text = (string) preg_replace('/^quem\s+(é|e)\s+(o|a)?\s*mais\s+prov[aá]vel\s+(de|a)\s+/iu', '', trim($text));
        $text = rtrim($text, " ?");
        return 'Quem é mais provável de ' . $text . '?';
    }

    protected function step(array $room, array $round, int $now): bool
    {
        $roundId = (int) $round['id'];
        if ($round['phase'] === 'voting') {
            $players = $this->repo()->players($roundId);
            $voted = array_map('intval', array_column($this->repo()->votes($roundId, 'main'), 'voter_id'));
            if (!$this->expired($round, $now) && array_diff(self::activeIds($players), $voted)) {
                return false;
            }
            $this->reveal($room, $round, $players, $now);
            return true;
        }
        if ($round['phase'] === 'reveal' && $this->expired($round, $now)) {
            $this->finishRound($room, $round, $now);
            return true;
        }
        return false;
    }

    private function reveal(array $room, array $round, array $players, int $now): void
    {
        $roundId = (int) $round['id'];
        $number = (int) $round['number'];
        $t = self::tally($this->repo()->votes($roundId, 'main'), array_keys($players));
        $counts = [];
        foreach ($t['counts'] as $pid => $votes) {
            if (isset($players[(int) $pid])) {
                $counts[] = ['player_id' => (int) $pid, 'nickname' => (string) $players[(int) $pid]['nickname'], 'votes' => $votes];
            }
        }
        $leaders = array_map('intval', $t['leaders']);
        $scoring = (bool) $this->ctx->setting($room, 'scoring', true);
        $points = $this->int($room, 'points', 1);
        if ($scoring) {
            foreach ($leaders as $pid) {
                $this->ctx->award($room, $number, $pid, $points, 'Mais votado na rodada ' . $number, "r$number:most");
            }
        }
        $public = $round['public'];
        $public['result'] = [
            'counts' => $counts,
            'leaders' => $leaders,
            'total_votes' => $t['total'],
            'voters' => count($players),
            'points' => $scoring ? $points : 0,
        ];
        $this->repo()->savePublic($roundId, $public);

        $summary = $leaders
            ? sprintf('%s (%d voto%s)', self::names($players, $leaders), $t['counts'][(string) $leaders[0]], $t['counts'][(string) $leaders[0]] === 1 ? '' : 's')
            : 'Ninguém votou nesta rodada.';
        $this->ctx->event($room, $number, $public['question'], (count($leaders) > 1 ? 'Empate: ' : '') . $summary, "r$number");
        $this->phase($room, $round, 'reveal', $this->int($room, 'reveal_time', 8), $now);
    }

    protected function handle(string $op, array $room, array $round, array $player, Input $in): array
    {
        if ($op !== 'vote') {
            throw new ApiException('Ação desconhecida.', 400, 'invalid_op');
        }
        $this->requirePhase($round, 'voting', 'A votação não está aberta.');
        $this->requireParticipant($round, $player);
        $target = $in->int('target', 1, PHP_INT_MAX >> 1, null, 'jogador');
        $players = $this->repo()->players((int) $round['id']);
        if ($target === (int) $player['id']) {
            throw new ApiException('Você não pode votar em si mesmo.', 422, 'self_vote');
        }
        if (!isset($players[$target]) || !$players[$target]['active']) {
            throw new ApiException('Escolha um jogador desta rodada.', 422, 'invalid_target');
        }
        $this->repo()->castVote((int) $round['id'], (int) $player['id'], 'main', (string) $target, time());
        $this->ctx->bump((int) $room['id'], time());
        return ['voted' => true];
    }

    protected function roundView(array $room, array $round, array $player, array $roundPlayers): array
    {
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        $votes = $this->repo()->votes($roundId, 'main');
        $voters = array_map('intval', array_column($votes, 'voter_id'));
        $myVote = $this->repo()->votesOf($roundId, $pid)['main'] ?? null;

        $candidates = [];
        $progress = [];
        foreach ($roundPlayers as $id => $rp) {
            $candidates[] = ['id' => (int) $id, 'nickname' => (string) $rp['nickname'], 'me' => (int) $id === $pid, 'active' => (bool) $rp['active']];
            $progress[(int) $id] = in_array((int) $id, $voters, true) ? 'submitted' : ($round['phase'] === 'voting' ? 'pending' : 'missed');
        }
        $view = [
            'question' => (string) $round['public']['question'],
            'category' => (string) $round['public']['category'],
            'candidates' => $candidates,
            'my_vote' => $myVote !== null ? (int) $myVote : null,
            'votes_cast' => count($voters),
            'voters' => count(self::activeIds($roundPlayers)),
            'progress' => $progress,
        ];
        // O resultado (contagem agregada) só existe depois da revelação.
        if ($round['phase'] === 'reveal') {
            $view['result'] = $round['public']['result'] ?? null;
        }
        return $view;
    }
}
