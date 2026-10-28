<?php
declare(strict_types=1);

namespace Games\TruthOrLie;

use App\Core\ApiException;
use App\Core\Input;
use App\Core\Text;
use App\Platform\RoundGame;

/**
 * "Mentira ou Verdade?"
 *
 * Rodada: writing ─▶ voting ─▶ reveal ─▶ próxima rodada
 *
 * - Cada jogador escreve uma afirmação sobre o tema e marca em segredo se é verdade (meta.truth).
 *   A marcação nunca vai ao navegador antes da revelação e não pode ser alterada (envio único).
 * - Quem não escreve no prazo fica fora da rodada (não vota).
 * - As afirmações recebem códigos públicos aleatórios e ordem embaralhada: na votação o navegador
 *   não sabe quem escreveu cada uma.
 * - Cada participante vota verdade/mentira em cada afirmação, exceto a própria (UNIQUE por afirmação).
 * - Menos de 2 afirmações: a rodada é anulada (sem pontos).
 *
 * Pontuação (configurável):
 *  - Acerto (voto igual à marcação do autor): points_correct.
 *  - Mentira que enganou a maioria (mais da metade dos votos foi "verdade"): points_fool para o autor.
 *  - Bônus opcional: verdade identificada pela maioria ⇒ points_truth para o autor.
 *  Afirmações sem votos não dão pontos ao autor.
 */
final class Game extends RoundGame
{
    private const MIN_LEN = 8;
    private const MAX_LEN = 200;

    public function normalizeSettings(array $s): array
    {
        if (!$s['categories'] && !$s['custom']) {
            throw new ApiException('Escolha pelo menos uma categoria ou escreva temas personalizados.', 422, 'invalid_input');
        }
        return $s;
    }

    protected function planRound(array $room, array $players, int $number, int $now): array
    {
        $chosen = (array) $this->ctx->setting($room, 'categories', []);
        $pool = [];
        foreach (require dirname(__DIR__) . '/content/themes.php' as $key => $cat) {
            if (in_array($key, $chosen, true)) {
                foreach ($cat['items'] as $i => $item) {
                    $pool[] = ['key' => "$key:$i", 'theme' => $item[0], 'example' => $item[1], 'category' => $cat['label']];
                }
            }
        }
        foreach ((array) $this->ctx->setting($room, 'custom', []) as $theme) {
            $pool[] = ['key' => 'custom:' . md5($theme), 'theme' => $theme, 'example' => null, 'category' => 'Personalizado'];
        }
        $pick = $this->pickPrompt($room, $pool);
        return [
            'phase' => 'writing',
            'seconds' => $this->int($room, 'write_time', 75),
            'prompt_key' => $pick['key'],
            'public' => ['theme' => $pick['theme'], 'example' => $pick['example'], 'category' => $pick['category']],
        ];
    }

    /** Autores (participantes que escreveram) ainda presentes na sala. */
    private function voters(int $roundId, array $players): array
    {
        $authors = array_map('intval', array_column($this->repo()->entries($roundId, 'statement'), 'player_id'));
        return array_values(array_intersect(self::activeIds($players), $authors));
    }

    protected function step(array $room, array $round, int $now): bool
    {
        $roundId = (int) $round['id'];
        $number = (int) $round['number'];
        $players = $this->repo()->players($roundId);
        switch ($round['phase']) {
            case 'writing':
                if (!$this->expired($round, $now) && !$this->allSubmitted($roundId, self::activeIds($players), 'statement')) {
                    return false;
                }
                if (count($this->repo()->entries($roundId, 'statement')) < 2) {
                    $this->ctx->event($room, $number, 'Tema: ' . $round['public']['theme'], 'Rodada anulada: menos de 2 afirmações enviadas.', "r$number");
                    $this->finishRound($room, $round, $now);
                    return true;
                }
                $this->repo()->assignPublicCodes($roundId, 'statement');
                $this->phase($room, $round, 'voting', $this->int($room, 'vote_time', 60), $now);
                return true;
            case 'voting':
                $statements = count($this->repo()->entries($roundId, 'statement'));
                $votes = $this->repo()->votes($roundId, 'st:');
                $perVoter = array_count_values(array_map('intval', array_column($votes, 'voter_id')));
                $pending = array_filter($this->voters($roundId, $players), static function (int $id) use ($perVoter, $statements): bool {
                    return ($perVoter[$id] ?? 0) < $statements - 1;
                });
                if (!$this->expired($round, $now) && $pending) {
                    return false;
                }
                $this->reveal($room, $round, $now);
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

    private function reveal(array $room, array $round, int $now): void
    {
        $roundId = (int) $round['id'];
        $number = (int) $round['number'];
        $votes = $this->repo()->votes($roundId, 'st:');
        $pCorrect = $this->int($room, 'points_correct', 1);
        $pFool = $this->int($room, 'points_fool', 2);
        $bonus = (bool) $this->ctx->setting($room, 'truth_bonus', false);
        $pTruth = $this->int($room, 'points_truth', 1);

        $items = [];
        $fooled = 0;
        foreach ($this->repo()->entries($roundId, 'statement') as $e) {
            $code = (string) $e['public_code'];
            $truth = (bool) $e['meta']['truth'];
            $t = 0;
            $f = 0;
            foreach ($votes as $v) {
                if ($v['ballot'] !== 'st:' . $code) {
                    continue;
                }
                $saidTrue = $v['target'] === 'true';
                $saidTrue ? $t++ : $f++;
                if ($saidTrue === $truth) {
                    $this->ctx->award($room, $number, (int) $v['voter_id'], $pCorrect, 'Acertou uma afirmação', "r$number:ok:$code");
                }
            }
            $total = $t + $f;
            $foolMajority = !$truth && $total > 0 && $t * 2 > $total;
            $truthFound = $truth && $total > 0 && $t * 2 > $total;
            if ($foolMajority) {
                $fooled++;
                $this->ctx->award($room, $number, (int) $e['player_id'], $pFool, 'Mentira enganou a maioria', "r$number:fool");
            }
            if ($bonus && $truthFound) {
                $this->ctx->award($room, $number, (int) $e['player_id'], $pTruth, 'Verdade identificada', "r$number:truth");
            }
            $items[] = [
                'code' => $code,
                'text' => (string) $e['body'],
                'truth' => $truth,
                'author' => (string) $e['nickname'],
                'author_id' => (int) $e['player_id'],
                'votes_true' => $t,
                'votes_false' => $f,
                'fooled' => $foolMajority,
            ];
        }
        $public = $round['public'];
        $public['result'] = ['items' => $items];
        $this->repo()->savePublic($roundId, $public);
        $this->ctx->event($room, $number, 'Tema: ' . $public['theme'], sprintf('%d afirmações; %d mentira(s) enganaram a maioria.', count($items), $fooled), "r$number");
        $this->phase($room, $round, 'reveal', $this->int($room, 'reveal_time', 20), $now);
    }

    protected function handle(string $op, array $room, array $round, array $player, Input $in): array
    {
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        if ($op === 'statement') {
            $this->requirePhase($round, 'writing', 'A etapa de escrita não está aberta.');
            $this->requireParticipant($round, $player);
            $text = Text::limited($in->string('text', 2000, 'afirmação'), self::MIN_LEN, self::MAX_LEN, 'A afirmação');
            $truth = $in->bool('truth');
            $this->repo()->addEntry($roundId, $pid, 'statement', $text, ['truth' => $truth], time());
            $this->ctx->bump((int) $room['id'], time());
            return ['statement' => $text, 'truth' => $truth];
        }
        if ($op === 'vote') {
            $this->requirePhase($round, 'voting', 'A votação não está aberta.');
            $this->requireParticipant($round, $player);
            if ($this->repo()->entryOf($roundId, $pid, 'statement') === null) {
                throw new ApiException('Você ficou fora desta rodada porque não enviou uma afirmação.', 403, 'excluded');
            }
            $code = strtoupper($in->string('code', 10, 'afirmação'));
            $value = $in->bool('value') ? 'true' : 'false';
            $entry = null;
            foreach ($this->repo()->entries($roundId, 'statement') as $e) {
                if ($e['public_code'] === $code) {
                    $entry = $e;
                }
            }
            if ($entry === null) {
                throw new ApiException('Afirmação não encontrada.', 422, 'invalid_target');
            }
            if ((int) $entry['player_id'] === $pid) {
                throw new ApiException('Você não pode votar na própria afirmação.', 422, 'own_entry');
            }
            $this->repo()->castVote($roundId, $pid, 'st:' . $code, $value, time());
            $this->ctx->bump((int) $room['id'], time());
            return ['voted' => true];
        }
        throw new ApiException('Ação desconhecida.', 400, 'invalid_op');
    }

    protected function roundView(array $room, array $round, array $player, array $roundPlayers): array
    {
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        $phase = (string) $round['phase'];
        $entries = $this->repo()->entries($roundId, 'statement');
        $authors = array_map('intval', array_column($entries, 'player_id'));
        $mine = $this->repo()->entryOf($roundId, $pid, 'statement');
        $myVotes = $this->repo()->votesOf($roundId, $pid);

        $progress = [];
        $votes = $this->repo()->votes($roundId, 'st:');
        $perVoter = array_count_values(array_map('intval', array_column($votes, 'voter_id')));
        foreach ($roundPlayers as $id => $rp) {
            if ($phase === 'writing') {
                $progress[(int) $id] = in_array((int) $id, $authors, true) ? 'submitted' : 'pending';
            } elseif ($phase === 'voting') {
                $progress[(int) $id] = !in_array((int) $id, $authors, true) ? 'excluded'
                    : (($perVoter[(int) $id] ?? 0) >= count($entries) - 1 ? 'submitted' : 'pending');
            }
        }

        $view = [
            'theme' => (string) $round['public']['theme'],
            'example' => $round['public']['example'],
            'category' => (string) $round['public']['category'],
            'min_len' => self::MIN_LEN,
            'max_len' => self::MAX_LEN,
            'mine' => $mine !== null ? ['text' => (string) $mine['body'], 'truth' => (bool) $mine['meta']['truth']] : null,
            'excluded' => $phase !== 'writing' && $mine === null,
            'written' => count($entries),
            'progress' => $progress,
        ];
        if ($phase === 'voting') {
            // Só texto + código público. Autor e marcação verdadeira/falsa ficam no servidor.
            $view['statements'] = array_map(static function (array $e) use ($pid, $myVotes): array {
                $code = (string) $e['public_code'];
                return [
                    'code' => $code,
                    'text' => (string) $e['body'],
                    'mine' => (int) $e['player_id'] === $pid,
                    'my_vote' => isset($myVotes['st:' . $code]) ? $myVotes['st:' . $code] === 'true' : null,
                ];
            }, $entries);
        }
        if ($phase === 'reveal') {
            $view['result'] = $round['public']['result'] ?? null;
        }
        return $view;
    }
}
