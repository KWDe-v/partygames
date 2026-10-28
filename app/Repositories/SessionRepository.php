<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Partidas (game_sessions): cada vez que o anfitrião inicia um jogo na sala.
 */
final class SessionRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(int $roomId, string $game, string $settingsJson, int $now): int
    {
        $number = 1 + (int) $this->db->fetchValue('SELECT COALESCE(MAX(number), 0) FROM game_sessions WHERE room_id = ?', [$roomId]);
        return $this->db->insert(
            "INSERT INTO game_sessions (room_id, number, game, settings, status, started_at) VALUES (?, ?, ?, ?, 'active', ?)",
            [$roomId, $number, $game, $settingsJson, $now]
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM game_sessions WHERE id = ?', [$id]);
    }

    /** Encerra a partida (idempotente: só altera se ainda estiver ativa). */
    public function finish(int $id, string $reason, int $now): bool
    {
        return $this->db->execute(
            "UPDATE game_sessions SET status = 'finished', end_reason = ?, ended_at = ? WHERE id = ? AND status = 'active'",
            [$reason, $now, $id]
        ) > 0;
    }
}
