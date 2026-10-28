<?php
declare(strict_types=1);

namespace App\Models;

final class Player extends Model
{
    protected const TABLE = 'players';
    protected const UPDATABLE = ['nickname', 'token_hash', 'identity_hash', 'status', 'connection', 'last_seen', 'left_at'];

    public function findByTokenHash(string $hash): ?array
    {
        return $this->db->fetchOne('SELECT * FROM players WHERE token_hash = ?', [$hash]);
    }

    public function findByIdentity(int $roomId, string $identityHash): ?array
    {
        return $this->db->fetchOne('SELECT * FROM players WHERE room_id = ? AND identity_hash = ?', [$roomId, $identityHash]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listActive(int $roomId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM players WHERE room_id = ? AND status = 'active' ORDER BY joined_at, id",
            [$roomId]
        );
    }

    public function countActive(int $roomId): int
    {
        return (int) $this->db->fetchValue("SELECT COUNT(*) FROM players WHERE room_id = ? AND status = 'active'", [$roomId]);
    }

    /** Jogadores ativos com contato recente, na ordem de entrada (regra de transferência do anfitrião). */
    public function listOnline(int $roomId, int $since): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM players WHERE room_id = ? AND status = 'active' AND last_seen >= ? ORDER BY joined_at, id",
            [$roomId, $since]
        );
    }

    public function nicknameTaken(int $roomId, string $nickname, int $exceptId = 0): bool
    {
        // A colação utf8mb4_unicode_ci compara sem diferenciar maiúsculas e acentos.
        return $this->db->fetchValue(
            "SELECT 1 FROM players WHERE room_id = ? AND status = 'active' AND nickname = ? AND id <> ? LIMIT 1",
            [$roomId, $nickname, $exceptId]
        ) !== null;
    }

    public function create(array $d): int
    {
        return $this->db->insert(
            "INSERT INTO players (room_id, nickname, token_hash, identity_hash, status, connection, joined_at, last_seen)
             VALUES (?, ?, ?, ?, 'active', 'online', ?, ?)",
            [$d['room_id'], $d['nickname'], $d['token_hash'], $d['identity_hash'], $d['now'], $d['now']]
        );
    }

    public function markOfflineBefore(int $roomId, int $before): int
    {
        return $this->db->execute(
            "UPDATE players SET connection = 'offline'
              WHERE room_id = ? AND status = 'active' AND connection = 'online' AND last_seen < ?",
            [$roomId, $before]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function listInactiveBefore(int $roomId, int $before): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM players WHERE room_id = ? AND status = 'active' AND last_seen < ?",
            [$roomId, $before]
        );
    }

    /** Atualiza a última atividade. Retorna true se o jogador estava marcado como desconectado. */
    public function touch(array $player, int $now): bool
    {
        $wasOffline = $player['connection'] === 'offline';
        if ($wasOffline || $now - (int) $player['last_seen'] >= 3) {
            $this->db->execute(
                "UPDATE players SET last_seen = ?, connection = 'online' WHERE id = ? AND status = 'active'",
                [$now, (int) $player['id']]
            );
        }
        return $wasOffline;
    }
}
