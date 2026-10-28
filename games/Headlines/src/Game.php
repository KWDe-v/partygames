<?php
declare(strict_types=1);

namespace Games\Headlines;

use App\Core\ApiException;
use App\Core\Input;
use App\Core\Text;
use App\Platform\RoundGame;

/**
 * "Manchete Absurda"
 *
 * Rodada: writing ─▶ voting ─▶ reveal ─▶ próxima rodada
 *
 * Usa os mecanismos existentes de envio e votação anônimos (game_entries com código público,
 * game_votes com UNIQUE). Durante a votação o navegador recebe só {código, texto, é minha?}.
 * Regras explícitas: uma manchete por jogador; menos de 2 manchetes ⇒ rodada anulada;
 * todos os participantes votam, nunca na própria; empate no topo ⇒ todas as empatadas vão para a
 * capa (empate declarado, sem desempate arbitrário); ninguém votou ⇒ sem capa e sem pontos.
 * Pontos: points_winner para cada manchete de capa + points_vote por voto recebido.
 */
final class Game extends RoundGame
{
    private const MIN_LEN = 8;
    private const MAX_LEN = 140;

    public function normalizeSettings(array $s): array
    {
        if (!$s['categories'] && !$s['custom']) {
            throw new ApiException('Escolha pelo menos uma editoria ou escreva temas personalizados.', 422, 'invalid_input');
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
                    $pool[] = ['key' => "$key:$i", 'theme' => $item[0], 'example' => $item[1], 'section' => $cat['label']];
                }
            }
        }
        foreach ((array) $this->ctx->setting($room, 'custom', []) as $theme) {
            $pool[] = ['key' => 'custom:' . md5($theme), 'theme' => $theme, 'example' => null, 'section' => 'Especial'];
        }
        $pick = $this->pickPrompt($room, $pool);
        return [
            'phase' => 'writing',
            'seconds' => $this->int($room, 'write_time', 60),
            'prompt_key' => $pick['key'],
            'public' => ['theme' => $pick['theme'], 'example' => $pick['example'], 'section' => $pick['section']],
        ];
    }

    protected function step(array $room, array $round, int $now): bool
    {
        $roundId = (int) $round['id'];
        $number = (int) $round['number'];
        $players = $this->repo()->players($roundId);
        switch ($round['phase']) {
            case 'writing':
                if (!$this->expired($round, $now) && !$this->allSubmitted($roundId, self::activeIds($players), 'headline')) {
                    return false;
                }
                if (count($this->repo()->entries($roundId, 'headline')) < 2) {
                    $this->ctx->event($room, $number, 'Tema: ' . $round['public']['theme'], 'Rodada anulada: menos de 2 manchetes.', "r$number");
                    $this->finishRound($room, $round, $now);
                    return true;
                }
                $this->repo()->assignPublicCodes($roundId, 'headline');
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
        $entries = $this->repo()->entries($roundId, 'headline');
        $t = self::tally($this->repo()->votes($roundId, 'best'), array_column($entries, 'public_code'));
        $perVote = $this->int($room, 'points_vote', 1);
        $items = [];
        $front = [];
        foreach ($entries as $e) {
            $code = (string) $e['public_code'];
            $votes = (int) ($t['counts'][$code] ?? 0);
            $isFront = in_array($code, $t['leaders'], true);
            $pid = (int) $e['player_id'];
            if ($isFront) {
                $front[] = (string) $e['nickname'];
                $this->ctx->award($room, $number, $pid, $this->int($room, 'points_winner', 3), 'Manchete de capa', "r$number:front");
            }
            if ($votes > 0 && $perVote > 0) {
                $this->ctx->award($room, $number, $pid, $votes * $perVote, 'Votos na manchete', "r$number:votes");
            }
            $items[] = ['code' => $code, 'text' => (string) $e['body'], 'author' => (string) $e['nickname'], 'votes' => $votes, 'front' => $isFront];
        }
        usort($items, static function (array $a, array $b): int {
            return $b['votes'] <=> $a['votes'];
        });
        $position = 0;
        $last = null;
        foreach ($items as $i => &$it) {
            if ($it['votes'] !== $last) {
                $position = $i + 1;
                $last = $it['votes'];
            }
            $it['position'] = $position;
        }
        unset($it);
        $public = $round['public'];
        $public['result'] = ['items' => $items, 'total_votes' => $t['total'], 'tie' => count($front) > 1];
        $this->repo()->savePublic($roundId, $public);
        $this->ctx->event($room, $number, 'Tema: ' . $public['theme'], $front
            ? (count($front) > 1 ? 'Empate na capa: ' : 'Capa: ') . implode(', ', $front) . ' — "' . Text::truncate($items[0]['text'], 80) . '"'
            : 'Ninguém votou nesta rodada.', "r$number");
        $reveal = $this->int($room, 'reveal_time', 15);
        $this->phase($room, $round, 'reveal', $reveal > 0 ? $reveal : null, $now);
    }

    protected function handle(string $op, array $room, array $round, array $player, Input $in): array
    {
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        $this->requireParticipant($round, $player);
        if ($op === 'headline') {
            $this->requirePhase($round, 'writing', 'A redação está fechada.');
            $text = Text::limited($in->string('text', 2000, 'manchete'), self::MIN_LEN, self::MAX_LEN, 'A manchete');
            $this->repo()->addEntry($roundId, $pid, 'headline', $text, [], time());
            $this->ctx->bump((int) $room['id'], time());
            return ['headline' => $text];
        }
        if ($op === 'vote') {
            $this->requirePhase($round, 'voting', 'A votação não está aberta.');
            $code = strtoupper($in->string('code', 10, 'manchete'));
            $target = null;
            foreach ($this->repo()->entries($roundId, 'headline') as $e) {
                if ($e['public_code'] === $code) {
                    $target = $e;
                }
            }
            if ($target === null) {
                throw new ApiException('Manchete não encontrada.', 422, 'invalid_target');
            }
            if ((int) $target['player_id'] === $pid) {
                throw new ApiException('Você não pode votar na própria manchete.', 422, 'own_entry');
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
        $entries = $this->repo()->entries($roundId, 'headline');
        $written = array_map('intval', array_column($entries, 'player_id'));
        $voters = array_map('intval', array_column($this->repo()->votes($roundId, 'best'), 'voter_id'));
        $mine = $this->repo()->entryOf($roundId, $pid, 'headline');
        $progress = [];
        foreach ($roundPlayers as $id => $rp) {
            if ($phase === 'writing') {
                $progress[(int) $id] = in_array((int) $id, $written, true) ? 'submitted' : 'pending';
            } elseif ($phase === 'voting') {
                $progress[(int) $id] = in_array((int) $id, $voters, true) ? 'submitted' : 'pending';
            }
        }
        $view = [
            'theme' => (string) $round['public']['theme'],
            'example' => $round['public']['example'],
            'section' => (string) $round['public']['section'],
            'min_len' => self::MIN_LEN,
            'max_len' => self::MAX_LEN,
            'my_headline' => $mine !== null ? (string) $mine['body'] : null,
            'written' => count($entries),
            'votes_cast' => count($voters),
            'progress' => $progress,
        ];
        if ($phase === 'voting') {
            $view['my_vote'] = $this->repo()->votesOf($roundId, $pid)['best'] ?? null;
            // Anonimato: só código público, texto e "é minha?".
            $view['headlines'] = array_map(static function (array $e) use ($pid): array {
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
