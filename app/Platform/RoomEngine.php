<?php
declare(strict_types=1);

namespace App\Platform;

use App\Core\ApiException;
use App\Core\Config;
use App\Core\Database;
use App\Core\Input;
use App\Models\Player;
use App\Models\Room;
use App\Repositories\ScoreRepository;
use App\Repositories\SessionRepository;
use App\Services\RateLimiter;

/**
 * Motor de salas da plataforma (comum a todos os jogos).
 *
 *   lobby ──start──▶ playing (fases definidas pelo jogo) ──▶ finished
 *     ▲                  │ (ex.: jogadores insuficientes)
 *     └──────────────────┘
 *
 * Toda mudança acontece em uma transação com a linha da sala bloqueada (SELECT ... FOR UPDATE)
 * e o estado é reavaliado depois do bloqueio: requisições simultâneas nunca avançam a mesma
 * fase duas vezes. Como o PHP não mantém processos ativos, os prazos são processados em tick(),
 * chamado a cada consulta de estado (polling) dos navegadores.
 */
final class RoomEngine
{
    private const SWEEP_INTERVAL = 5;

    /** @var Database */
    private $db;
    /** @var Room */
    private $rooms;
    /** @var Player */
    private $players;
    /** @var array<string, AbstractGame> */
    private $games = [];

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->rooms = new Room($db);
        $this->players = new Player($db);
    }

    public static function offlineAfter(): int
    {
        return max(5, Config::int('platform.offline_after', 15));
    }

    public static function requiredPlayers(array $room, array $manifest): int
    {
        return max((int) ($manifest['players'][0] ?? 2), (int) $room['min_players']);
    }

    public function game(array $room): AbstractGame
    {
        $slug = (string) $room['game'];
        if (!isset($this->games[$slug])) {
            $this->games[$slug] = GameRegistry::instance($slug, $this, $this->db);
        }
        return $this->games[$slug];
    }

    /** @return mixed */
    public function withRoomLock(int $roomId, callable $fn)
    {
        return $this->db->transaction(function () use ($roomId, $fn) {
            $room = $this->rooms->lock($roomId);
            if ($room === null) {
                throw new ApiException('Sala não encontrada.', 404, 'room_not_found');
            }
            return $fn($room);
        });
    }

    // ------------------------------------------------------------------ ações

    public function startGame(array $player): void
    {
        $this->withRoomLock((int) $player['room_id'], function (array $room) use ($player): void {
            $this->assertHost($room, $player);
            if ($room['status'] !== 'lobby') {
                throw new ApiException('A partida já está em andamento.', 409, 'invalid_state');
            }
            $now = time();
            $need = self::requiredPlayers($room, GameRegistry::get((string) $room['game']));
            $online = $this->players->listOnline((int) $room['id'], $now - self::offlineAfter());
            if (count($online) < $need) {
                throw new ApiException(sprintf('São necessários pelo menos %d jogadores conectados para começar.', $need), 409, 'not_enough_players');
            }
            // Nova partida: placar e histórico começam do zero; a sala continua a mesma.
            $sessionId = (new SessionRepository($this->db))->create((int) $room['id'], (string) $room['game'], (string) $room['settings'], $now);
            $this->rooms->update((int) $room['id'], [
                'notice' => null,
                'session_id' => $sessionId,
                'round_number' => 0,
                'current_round_id' => null,
            ]);
            $this->game($room)->start($this->rooms->find((int) $room['id']), $now);
            $this->rooms->bump((int) $room['id'], $now);
            $this->advanceIfDue((int) $room['id'], $now);
        });
    }

    /** Ação específica do jogo (enviar resposta, votar...). */
    public function gameAction(array $player, string $op, Input $in): array
    {
        if (!preg_match('/^[a-z][a-z0-9_]{0,39}$/', $op)) {
            throw new ApiException('Ação inválida.', 400, 'invalid_op');
        }
        // Limite contra abuso: no máximo 40 ações de jogo a cada 20 s por jogador.
        $limiter = new RateLimiter($this->db);
        $bucket = 'action|' . (int) $player['id'];
        $limiter->assertAllowed($bucket, 40, 20);
        $limiter->hit($bucket, 20);
        return $this->withRoomLock((int) $player['room_id'], function (array $room) use ($player, $op, $in): array {
            if ($room['status'] !== 'playing') {
                throw new ApiException('A partida não está em andamento.', 409, 'invalid_state');
            }
            $result = $this->game($room)->action($op, $room, $player, $in);
            $this->advanceIfDue((int) $room['id'], time());
            return $result;
        });
    }

    public function leave(array $player): void
    {
        $this->withRoomLock((int) $player['room_id'], function (array $room) use ($player): void {
            $now = time();
            $pid = (int) $player['id'];
            $this->players->update($pid, ['status' => 'left', 'left_at' => $now, 'connection' => 'offline']);
            if ($room['status'] === 'finished') {
                return;
            }
            if ($room['status'] === 'playing') {
                $this->game($room)->playerLeft($room, $pid, $now);
            }
            if ((int) $room['host_player_id'] === $pid) {
                $this->transferHost($room, $now, true);
            }
            if ($this->players->countActive((int) $room['id']) === 0) {
                $this->finishRoom($this->rooms->find((int) $room['id']), 'empty', $now);
                return;
            }
            $this->rooms->bump((int) $room['id'], $now);
            $this->advanceIfDue((int) $room['id'], $now);
        });
    }

    /**
     * Anfitrião: durante o jogo, encerra a partida (todos voltam ao lobby com o placar);
     * no lobby, fecha a sala para todos.
     */
    public function endGame(array $player): void
    {
        $this->withRoomLock((int) $player['room_id'], function (array $room) use ($player): void {
            $this->assertHost($room, $player);
            if ($room['status'] === 'playing') {
                $this->endMatch($room, 'host_ended', time(), 'O anfitrião encerrou a partida.');
            } elseif ($room['status'] === 'lobby') {
                $this->finishRoom($room, 'host_ended', time());
            }
        });
    }

    /** Fim da partida: placar final registrado, sala volta ao lobby. */
    public function endMatch(array $room, string $reason, int $now, ?string $notice = null): void
    {
        if ($room['status'] === 'playing') {
            $this->game($room)->finished($room, $now);
        }
        $sessionId = (int) $room['session_id'];
        if ($sessionId > 0 && (new SessionRepository($this->db))->finish($sessionId, $reason, $now)) {
            $summary = $this->winnersSummary($room);
            if ($summary !== null) {
                (new ScoreRepository($this->db))->event($sessionId, (int) $room['round_number'], 'match_end', 'match_end', [
                    'title' => 'Fim de jogo',
                    'text' => $summary,
                ], $now);
                if ($notice === null) {
                    $notice = 'Fim de jogo! ' . $summary;
                }
            }
        }
        $this->rooms->update((int) $room['id'], [
            'status' => 'lobby',
            'phase' => null,
            'phase_ends_at' => null,
            'current_round_id' => null,
            'notice' => $notice ?? 'Fim de jogo!',
        ]);
        $this->rooms->bump((int) $room['id'], $now);
    }

    /** "Ana venceu com 7 pontos" / "Empate entre Ana e Bia com 5 pontos" (null se ninguém pontuou). */
    private function winnersSummary(array $room): ?string
    {
        $board = (new ScoreRepository($this->db))->scoreboard((int) $room['session_id'], (int) $room['id'], 0);
        $top = array_values(array_filter($board, static function (array $r): bool {
            return $r['position'] === 1 && $r['total'] > 0;
        }));
        if (!$top) {
            return null;
        }
        $pts = $top[0]['total'] === 1 ? '1 ponto' : $top[0]['total'] . ' pontos';
        if (count($top) === 1) {
            return sprintf('%s venceu com %s.', $top[0]['nickname'], $pts);
        }
        $names = array_column($top, 'nickname');
        $last = array_pop($names);
        return sprintf('Empate entre %s e %s com %s.', implode(', ', $names), $last, $pts);
    }

    // ------------------------------------------------------------------ relógio

    /** Verificação barata sem bloqueio; só bloqueia a sala se houver prazo vencido ou varredura pendente. */
    public function tick(int $roomId): void
    {
        $room = $this->rooms->find($roomId);
        if ($room === null || $room['status'] === 'finished') {
            return;
        }
        $now = time();
        $due = $now >= (int) $room['sweep_at']
            || ($room['status'] === 'playing' && $room['phase_ends_at'] !== null && $now >= (int) $room['phase_ends_at']);
        if (!$due) {
            return;
        }
        $this->withRoomLock($roomId, function (array $room) use ($now): void {
            if ($room['status'] === 'finished') {
                return;
            }
            if ($now >= (int) $room['sweep_at'] && !$this->sweep($room, $now)) {
                return;
            }
            $this->advanceIfDue((int) $room['id'], $now);
        });
    }

    /** Deixa o jogo aplicar todas as transições vencidas, em sequência. */
    public function advanceIfDue(int $roomId, int $now): void
    {
        for ($i = 0; $i < 8; $i++) {
            $room = $this->rooms->find($roomId);
            if ($room === null || $room['status'] !== 'playing' || !$this->game($room)->advance($room, $now)) {
                return;
            }
        }
    }

    /** Presença, abandono, anfitrião e expiração. Retorna false se a sala foi encerrada. */
    private function sweep(array $room, int $now): bool
    {
        $roomId = (int) $room['id'];
        $changed = false;
        $this->rooms->update($roomId, ['sweep_at' => $now + self::SWEEP_INTERVAL]);

        if ($this->players->markOfflineBefore($roomId, $now - self::offlineAfter()) > 0) {
            $changed = true;
        }

        $abandonAfter = max(60, Config::int('platform.abandon_after', 180));
        foreach ($this->players->listInactiveBefore($roomId, $now - $abandonAfter) as $p) {
            $this->players->update((int) $p['id'], ['status' => 'abandoned', 'left_at' => $now, 'connection' => 'offline']);
            if ($room['status'] === 'playing') {
                $this->game($room)->playerLeft($room, (int) $p['id'], $now);
            }
            $changed = true;
        }

        if ($this->transferHost($room, $now, false)) {
            $changed = true;
        }

        $active = $this->players->countActive($roomId);
        $idleSince = $now - max(300, Config::int('platform.room_idle_expire', 1800));
        if ($active === 0 || count($this->players->listOnline($roomId, $idleSince)) === 0) {
            $this->finishRoom($this->rooms->find($roomId), $active === 0 ? 'empty' : 'expired', $now);
            return false;
        }
        if ($changed) {
            $this->rooms->bump($roomId, $now);
        }
        return true;
    }

    /**
     * Se o anfitrião saiu ou está desconectado há mais de host_transfer_after segundos,
     * o controle passa ao jogador conectado mais antigo da sala.
     */
    private function transferHost(array $room, int $now, bool $force): bool
    {
        $roomId = (int) $room['id'];
        $hostId = (int) $room['host_player_id'];
        $host = $hostId > 0 ? $this->players->find($hostId) : null;
        if (!$force && $host !== null && $host['status'] === 'active'
            && (int) $host['last_seen'] >= $now - max(10, Config::int('platform.host_transfer_after', 25))) {
            return false;
        }
        $candidates = $this->players->listOnline($roomId, $now - self::offlineAfter());
        if ($force && !$candidates) {
            $candidates = $this->players->listActive($roomId);
        }
        foreach ($candidates as $c) {
            if ((int) $c['id'] !== $hostId && $c['status'] === 'active') {
                $this->rooms->update($roomId, [
                    'host_player_id' => (int) $c['id'],
                    'notice' => sprintf('%s agora é o anfitrião da sala.', $c['nickname']),
                ]);
                return true;
            }
        }
        return false;
    }

    public function finishRoom(array $room, string $reason, int $now): void
    {
        if ($room['status'] === 'playing') {
            $this->game($room)->finished($room, $now);
        }
        if ((int) $room['session_id'] > 0) {
            (new SessionRepository($this->db))->finish((int) $room['session_id'], $reason, $now);
        }
        $this->rooms->update((int) $room['id'], [
            'status' => 'finished',
            'phase' => null,
            'phase_ends_at' => null,
            'end_reason' => $reason,
            'finished_at' => $now,
            'notice' => null,
        ]);
        $this->rooms->bump((int) $room['id'], $now);
    }

    private function assertHost(array $room, array $player): void
    {
        if ((int) $room['host_player_id'] !== (int) $player['id']) {
            throw new ApiException('Apenas o anfitrião pode fazer isso.', 403, 'not_host');
        }
    }
}
