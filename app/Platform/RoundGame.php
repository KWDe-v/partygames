<?php
declare(strict_types=1);

namespace App\Platform;

use App\Core\ApiException;
use App\Core\Input;
use App\Repositories\RoundRepository;
use App\Services\Shuffler;

/**
 * Base para jogos organizados em rodadas (tabelas genéricas game_rounds/entries/votes).
 *
 * O jogo implementa:
 *   planRound()  — conteúdo da rodada (pergunta, palavra...), papéis secretos e primeira fase;
 *   step()       — transições de fase (chamado quando o prazo vence ou após cada ação);
 *   handle()     — ações dos jogadores;
 *   roundView()  — o que cada jogador pode ver.
 * A base cuida de numeração, prazos, participantes, envios/votos únicos, pular fase e fim da partida.
 */
abstract class RoundGame extends AbstractGame
{
    /** @var RoundRepository|null */
    private $repo;

    abstract protected function planRound(array $room, array $players, int $number, int $now): array;

    abstract protected function step(array $room, array $round, int $now): bool;

    abstract protected function handle(string $op, array $room, array $round, array $player, Input $in): array;

    abstract protected function roundView(array $room, array $round, array $player, array $roundPlayers): array;

    /** Fases que o anfitrião pode encerrar antes do prazo (ex.: revelação, discussão). */
    protected function skippablePhases(): array
    {
        return ['reveal'];
    }

    public function joinablePhases(): array
    {
        return ['reveal'];
    }

    protected function repo(): RoundRepository
    {
        if ($this->repo === null) {
            $this->repo = $this->ctx->roundsRepo();
        }
        return $this->repo;
    }

    protected function totalRounds(array $room): int
    {
        return max(1, (int) $this->ctx->setting($room, 'rounds', 5));
    }

    protected function int(array $room, string $key, int $default): int
    {
        return (int) $this->ctx->setting($room, $key, $default);
    }

    // ------------------------------------------------------------------ ciclo de vida

    public function start(array $room, int $now): void
    {
        $this->nextRound($room, $now);
    }

    /** Abre a próxima rodada com os jogadores conectados, ou encerra a partida. */
    protected function nextRound(array $room, int $now): void
    {
        $room = $this->ctx->fresh($room);
        $roomId = (int) $room['id'];
        $number = (int) $room['round_number'] + 1;
        if ($number > $this->totalRounds($room)) {
            $this->ctx->endMatch($room, 'rounds_done', $now);
            return;
        }
        $online = $this->ctx->onlinePlayers($roomId, $now);
        $need = $this->ctx->requiredPlayers($room);
        if (count($online) < $need) {
            $this->ctx->backToLobby($roomId, sprintf('A partida foi interrompida: são necessários pelo menos %d jogadores conectados.', $need));
            return;
        }
        $plan = $this->planRound($room, $online, $number, $now);
        $seconds = $plan['seconds'] ?? null;
        $endsAt = $seconds !== null ? $now + (int) $seconds : null;
        $roundId = $this->repo()->create(
            $this->ctx->sessionId($room), $roomId, $number, (string) $plan['phase'], $now, $endsAt,
            $plan['prompt_key'] ?? null, $plan['public'] ?? [], $plan['secret'] ?? []
        );
        $players = $plan['players'] ?? array_fill_keys(array_map('intval', array_column($online, 'id')), []);
        $this->repo()->addPlayers($roundId, $players);
        $this->ctx->setPhase($roomId, (string) $plan['phase'], $plan['wake_at'] ?? $endsAt, [
            'current_round_id' => $roundId,
            'round_number' => $number,
            'notice' => null,
        ]);
        $this->ctx->bump($roomId, $now);
    }

    /**
     * Muda a fase da rodada. $seconds null = sem prazo (avança por ação). $wakeAt permite acordar
     * o jogo antes do fim da fase (ex.: liberar uma pista no meio da investigação).
     */
    protected function phase(array $room, array $round, string $phase, ?int $seconds, int $now, ?int $wakeAt = null): void
    {
        $endsAt = $seconds !== null ? $now + $seconds : null;
        $this->repo()->setPhase((int) $round['id'], $phase, $now, $endsAt);
        $this->ctx->setPhase((int) $room['id'], $phase, $wakeAt ?? $endsAt);
        $this->ctx->bump((int) $room['id'], $now);
    }

    /** Agenda o próximo "despertar" do jogo sem mudar a fase. */
    protected function wakeAt(array $room, ?int $at): void
    {
        $this->ctx->rooms->update((int) $room['id'], ['phase_ends_at' => $at]);
    }

    protected function finishRound(array $room, array $round, int $now): void
    {
        $this->repo()->end((int) $round['id'], $now);
        $this->nextRound($room, $now);
    }

    public function advance(array $room, int $now): bool
    {
        $round = $this->currentRound($room);
        if ($round === null || $round['phase'] !== $room['phase']) {
            return false;
        }
        return $this->step($room, $round, $now);
    }

    public function action(string $op, array $room, array $player, Input $in): array
    {
        $round = $this->currentRound($room);
        if ($round === null) {
            throw new ApiException('Nenhuma rodada em andamento.', 409, 'invalid_state');
        }
        if ($op === 'skip') {
            // O anfitrião encerra a fase atual antes do prazo. 'phase' torna a ação idempotente.
            $this->ctx->assertHost($room, $player);
            $expected = $in->string('phase', 40, 'fase');
            if ($expected === $round['phase'] && in_array($round['phase'], $this->skippablePhases(), true)) {
                $now = time();
                $this->repo()->setPhase((int) $round['id'], (string) $round['phase'], (int) $round['phase_started_at'], $now);
                $this->ctx->rooms->update((int) $room['id'], ['phase_ends_at' => $now]);
            }
            return ['skipped' => true];
        }
        return $this->handle($op, $room, $round, $player, $in);
    }

    public function view(array $room, array $player, array $players): array
    {
        $round = $this->currentRound($room);
        if ($round === null) {
            return [];
        }
        $roundPlayers = $this->repo()->players((int) $round['id']);
        $view = [
            'round' => [
                'id' => (int) $round['id'],
                'number' => (int) $round['number'],
                'total' => $this->totalRounds($room),
                'phase' => (string) $round['phase'],
                'started_at' => (int) $round['phase_started_at'] * 1000,
                'ends_at' => $round['phase_ends_at'] !== null ? (int) $round['phase_ends_at'] * 1000 : null,
                'skippable' => in_array($round['phase'], $this->skippablePhases(), true),
            ],
            'in_round' => isset($roundPlayers[(int) $player['id']]),
        ];
        return array_merge($view, $this->roundView($room, $round, $player, $roundPlayers));
    }

    // ------------------------------------------------------------------ utilitários para os jogos

    protected function currentRound(array $room): ?array
    {
        if (!$room['current_round_id']) {
            return null;
        }
        $round = $this->repo()->find((int) $room['current_round_id']);
        return $round !== null && (int) $round['session_id'] === $this->ctx->sessionId($room) ? $round : null;
    }

    protected function expired(array $round, int $now): bool
    {
        return $round['phase_ends_at'] !== null && $now >= (int) $round['phase_ends_at'];
    }

    /** Confere fase e prazo oficiais do servidor. */
    protected function requirePhase(array $round, string $phase, string $message): void
    {
        if ($round['phase'] !== $phase) {
            throw new ApiException($message, 409, 'invalid_state');
        }
        $t = microtime(true);
        if ($t < (int) $round['phase_started_at']) {
            throw new ApiException('Esta etapa ainda não começou.', 409, 'not_started');
        }
        if ($round['phase_ends_at'] !== null && $t >= (int) $round['phase_ends_at']) {
            throw new ApiException('Tempo esgotado!', 409, 'time_over');
        }
    }

    /** O jogador precisa ter sido convocado para esta rodada. */
    protected function requireParticipant(array $round, array $player): array
    {
        $rp = $this->repo()->players((int) $round['id'])[(int) $player['id']] ?? null;
        if ($rp === null) {
            throw new ApiException('Você não participa desta rodada. Aguarde a próxima.', 403, 'not_participant');
        }
        return $rp;
    }

    /** Participantes ainda presentes na sala (quem saiu não é aguardado). */
    protected static function activeIds(array $roundPlayers): array
    {
        $ids = [];
        foreach ($roundPlayers as $pid => $rp) {
            if (!empty($rp['active'])) {
                $ids[] = (int) $pid;
            }
        }
        return $ids;
    }

    /** Todos os $playerIds já enviaram um item do tipo $kind? */
    protected function allSubmitted(int $roundId, array $playerIds, string $kind): bool
    {
        $done = array_map('intval', array_column($this->repo()->entries($roundId, $kind), 'player_id'));
        return !array_diff($playerIds, $done);
    }

    /** Escolhe um item do acervo que ainda não saiu na partida (se acabarem, permite repetir). */
    protected function pickPrompt(array $room, array $pool): ?array
    {
        if (!$pool) {
            return null;
        }
        $used = array_flip($this->repo()->usedPromptKeys($this->ctx->sessionId($room)));
        $fresh = array_values(array_filter($pool, static function (array $p) use ($used): bool {
            return !isset($used[$p['key']]);
        }));
        $candidates = $fresh ?: array_values($pool);
        return $candidates[random_int(0, count($candidates) - 1)];
    }

    /**
     * Contagem de votos por alvo e líderes (todos os empatados no topo; vazio se ninguém votou).
     *
     * @return array{counts: array<string,int>, leaders: string[], total: int}
     */
    protected static function tally(array $votes, array $targets = []): array
    {
        $counts = array_fill_keys(array_map('strval', $targets), 0);
        foreach ($votes as $v) {
            $t = (string) $v['target'];
            $counts[$t] = ($counts[$t] ?? 0) + 1;
        }
        arsort($counts);
        $max = $counts ? max($counts) : 0;
        $leaders = $max > 0 ? array_map('strval', array_keys(array_filter($counts, static function (int $c) use ($max): bool {
            return $c === $max;
        }))) : [];
        return ['counts' => $counts, 'leaders' => $leaders, 'total' => array_sum($counts)];
    }

    protected static function names(array $roundPlayers, array $ids): string
    {
        $names = [];
        foreach ($ids as $id) {
            if (isset($roundPlayers[(int) $id])) {
                $names[] = (string) $roundPlayers[(int) $id]['nickname'];
            }
        }
        if (count($names) <= 1) {
            return (string) ($names[0] ?? '');
        }
        $last = array_pop($names);
        return implode(', ', $names) . ' e ' . $last;
    }

    protected static function shuffle(array $items): array
    {
        return Shuffler::shuffle($items);
    }
}
