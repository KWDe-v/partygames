<?php
declare(strict_types=1);

namespace Games\AbsurdAnswers;

use App\Core\ApiException;
use App\Core\Input;
use App\Core\Text;
use App\Platform\RoundGame;

/**
 * "A Resposta Mais Absurdamente Errada"
 *
 * Rodada: answering ─▶ voting ─▶ reveal ─▶ próxima rodada
 *
 * Anonimato (aplicado na API, não só na tela): durante a votação o navegador recebe apenas
 * {código público aleatório, texto, "é minha?"}. Autor, id interno e contagem de votos ficam no
 * servidor até a revelação.
 *
 * Regras explícitas:
 *  - Uma resposta por jogador (UNIQUE), sem edição.
 *  - Menos de 2 respostas válidas ⇒ rodada anulada, sem votação e sem pontos.
 *  - Votam todos os participantes da rodada (inclusive quem não respondeu), um voto cada,
 *    nunca na própria resposta. Respostas de quem desconectou continuam valendo.
 *  - Ninguém votou ⇒ nenhum ponto. Empate no topo ⇒ todos os empatados recebem points_winner.
 */
final class Game extends RoundGame
{
    private const MIN_LEN = 2;
    private const MAX_LEN = 200;

    public function normalizeSettings(array $s): array
    {
        if (!$s['categories'] && !$s['custom']) {
            throw new ApiException('Escolha pelo menos uma categoria ou escreva perguntas personalizadas.', 422, 'invalid_input');
        }
        return $s;
    }

    protected function planRound(array $room, array $players, int $number, int $now): array
    {
        $chosen = (array) $this->ctx->setting($room, 'categories', []);
        $pool = [];
        foreach (require dirname(__DIR__) . '/content/questions.php' as $key => $cat) {
            if (in_array($key, $chosen, true)) {
                foreach ($cat['items'] as $i => $q) {
                    $pool[] = ['key' => "$key:$i", 'question' => $q, 'category' => $cat['label']];
                }
            }
        }
        foreach ((array) $this->ctx->setting($room, 'custom', []) as $q) {
            $pool[] = ['key' => 'custom:' . md5($q), 'question' => $q, 'category' => 'Personalizada'];
        }
        $pick = $this->pickPrompt($room, $pool);
        return [
            'phase' => 'answering',
            'seconds' => $this->int($room, 'answer_time', 60),
            'prompt_key' => $pick['key'],
            'public' => ['question' => $pick['question'], 'category' => $pick['category']],
        ];
    }

    protected function step(array $room, array $round, int $now): bool
    {
        $roundId = (int) $round['id'];
        $number = (int) $round['number'];
        $players = $this->repo()->players($roundId);
        switch ($round['phase']) {
            case 'answering':
                if (!$this->expired($round, $now) && !$this->allSubmitted($roundId, self::activeIds($players), 'answer')) {
                    return false;
                }
                if (count($this->repo()->entries($roundId, 'answer')) < 2) {
                    $this->ctx->event($room, $number, (string) $round['public']['question'], 'Rodada anulada: menos de 2 respostas.', "r$number");
                    $this->finishRound($room, $round, $now);
                    return true;
                }
                $this->repo()->assignPublicCodes($roundId, 'answer');
                $this->phase($room, $round, 'voting', $this->int($room, 'vote_time', 40), $now);
                return true;
            case 'voting':
                $voted = array_map('intval', array_column($this->repo()->votes($roundId, 'best'), 'voter_id'));
                if (!$this->expired($round, $now) && array_diff(self::activeIds($players), $voted)) {
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
        $entries = $this->repo()->entries($roundId, 'answer');
        $t = self::tally($this->repo()->votes($roundId, 'best'), array_column($entries, 'public_code'));
        $points = $this->int($room, 'points_winner', 3);
        $items = [];
        $winners = [];
        foreach ($entries as $e) {
            $code = (string) $e['public_code'];
            $isWinner = in_array($code, $t['leaders'], true);
            if ($isWinner) {
                $winners[] = (string) $e['nickname'];
                $this->ctx->award($room, $number, (int) $e['player_id'], $points, 'Resposta mais votada', "r$number:best");
            }
            $items[] = [
                'code' => $code,
                'text' => (string) $e['body'],
                'author' => (string) $e['nickname'],
                'votes' => $t['counts'][$code] ?? 0,
                'winner' => $isWinner,
            ];
        }
        usort($items, static function (array $x, array $y): int {
            return $y['votes'] <=> $x['votes'];
        });
        $public = $round['public'];
        $public['result'] = ['items' => $items, 'total_votes' => $t['total']];
        $this->repo()->savePublic($roundId, $public);
        $this->ctx->event($room, $number, (string) $public['question'], $winners
            ? (count($winners) > 1 ? 'Empate: ' : '') . implode(', ', $winners) . ' — "' . Text::truncate($items[0]['text'], 80) . '"'
            : 'Ninguém votou nesta rodada.', "r$number");
        $reveal = $this->int($room, 'reveal_time', 15);
        $this->phase($room, $round, 'reveal', $reveal > 0 ? $reveal : null, $now);
    }

    protected function handle(string $op, array $room, array $round, array $player, Input $in): array
    {
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        if ($op === 'answer') {
            $this->requirePhase($round, 'answering', 'A etapa de respostas não está aberta.');
            $this->requireParticipant($round, $player);
            $text = Text::limited($in->string('text', 2000, 'resposta'), self::MIN_LEN, self::MAX_LEN, 'A resposta');
            $this->repo()->addEntry($roundId, $pid, 'answer', $text, [], time());
            $this->ctx->bump((int) $room['id'], time());
            return ['answer' => $text];
        }
        if ($op === 'vote') {
            $this->requirePhase($round, 'voting', 'A votação não está aberta.');
            $this->requireParticipant($round, $player);
            $code = strtoupper($in->string('code', 10, 'resposta'));
            $target = null;
            foreach ($this->repo()->entries($roundId, 'answer') as $e) {
                if ($e['public_code'] === $code) {
                    $target = $e;
                }
            }
            if ($target === null) {
                throw new ApiException('Resposta não encontrada.', 422, 'invalid_target');
            }
            if ((int) $target['player_id'] === $pid) {
                throw new ApiException('Você não pode votar na própria resposta.', 422, 'own_entry');
            }
            $this->repo()->castVote($roundId, $pid, 'best', $code, time());
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
        $entries = $this->repo()->entries($roundId, 'answer');
        $answered = array_map('intval', array_column($entries, 'player_id'));
        $voters = array_map('intval', array_column($this->repo()->votes($roundId, 'best'), 'voter_id'));
        $mine = $this->repo()->entryOf($roundId, $pid, 'answer');
        $progress = [];
        foreach ($roundPlayers as $id => $rp) {
            if ($phase === 'answering') {
                $progress[(int) $id] = in_array((int) $id, $answered, true) ? 'submitted' : 'pending';
            } elseif ($phase === 'voting') {
                $progress[(int) $id] = in_array((int) $id, $voters, true) ? 'submitted' : 'pending';
            }
        }
        $view = [
            'question' => (string) $round['public']['question'],
            'category' => (string) $round['public']['category'],
            'min_len' => self::MIN_LEN,
            'max_len' => self::MAX_LEN,
            'my_answer' => $mine !== null ? (string) $mine['body'] : null,
            'answered' => count($entries),
            'votes_cast' => count($voters),
            'progress' => $progress,
        ];
        if ($phase === 'voting') {
            $myVote = $this->repo()->votesOf($roundId, $pid)['best'] ?? null;
            $view['my_vote'] = $myVote;
            $view['answers'] = array_map(static function (array $e) use ($pid): array {
                return ['code' => (string) $e['public_code'], 'text' => (string) $e['body'], 'mine' => (int) $e['player_id'] === $pid];
            }, $entries);
        }
        if ($phase === 'reveal') {
            $view['result'] = $round['public']['result'] ?? null;
            $view['manual'] = $round['phase_ends_at'] === null;
        }
        return $view;
    }
}
