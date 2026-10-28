<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Text;

/**
 * Pontuação e histórico compartilhados por todos os jogos.
 * Os pontos são sempre calculados no servidor; ref_key garante que cada ponto seja contado uma vez.
 */
final class ScoreRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /** Concede pontos. Retorna false se esse mesmo ponto (ref_key) já tinha sido concedido. */
    public function award(int $sessionId, int $playerId, int $roundNumber, int $points, string $reason, string $refKey, int $now): bool
    {
        return $this->db->execute(
            'INSERT IGNORE INTO score_entries (session_id, player_id, round_number, points, reason, ref_key, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$sessionId, $playerId, $roundNumber, $points, Text::truncate($reason, 120), substr($refKey, 0, 64), $now]
        ) > 0;
    }

    /**
     * Placar: jogadores ativos da sala e quem pontuou na partida, com total, pontos da rodada e posição.
     * Posições empatadas recebem o mesmo número (1, 1, 3...).
     */
    public function scoreboard(int $sessionId, int $roomId, int $roundNumber): array
    {
        $rows = $this->db->fetchAll(
            "SELECT p.id, p.nickname, p.status,
                    COALESCE(SUM(s.points), 0) AS total,
                    COALESCE(SUM(CASE WHEN s.round_number = ? THEN s.points ELSE 0 END), 0) AS round_points
               FROM players p
               LEFT JOIN score_entries s ON s.player_id = p.id AND s.session_id = ?
              WHERE p.room_id = ? AND (p.status = 'active' OR s.id IS NOT NULL)
              GROUP BY p.id, p.nickname, p.status
              ORDER BY total DESC, p.joined_at, p.id",
            [$roundNumber, $sessionId, $roomId]
        );
        $out = [];
        $position = 0;
        $lastTotal = null;
        foreach ($rows as $i => $r) {
            $total = (int) $r['total'];
            if ($total !== $lastTotal) {
                $position = $i + 1;
                $lastTotal = $total;
            }
            $out[] = [
                'id' => (int) $r['id'],
                'nickname' => (string) $r['nickname'],
                'total' => $total,
                'round_points' => (int) $r['round_points'],
                'position' => $position,
                'left' => $r['status'] !== 'active',
            ];
        }
        return $out;
    }

    /** Registra um resumo público no histórico (idempotente por ref_key). */
    public function event(int $sessionId, int $roundNumber, string $type, string $refKey, array $payload, int $now): void
    {
        $this->db->execute(
            'INSERT IGNORE INTO game_events (session_id, round_number, type, ref_key, payload, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$sessionId, $roundNumber, $type, substr($refKey, 0, 64), json_encode($payload, JSON_UNESCAPED_UNICODE), $now]
        );
    }

    /** @return array<int, array<string, mixed>> mais recentes primeiro */
    public function history(int $sessionId, int $limit = 30): array
    {
        $out = [];
        foreach ($this->db->fetchAll(
            'SELECT round_number, type, payload, created_at FROM game_events WHERE session_id = ? ORDER BY id DESC LIMIT ' . max(1, min(100, $limit)),
            [$sessionId]
        ) as $row) {
            $payload = json_decode((string) $row['payload'], true);
            $out[] = [
                'round' => (int) $row['round_number'],
                'type' => (string) $row['type'],
                'title' => (string) ($payload['title'] ?? ''),
                'text' => (string) ($payload['text'] ?? ''),
            ];
        }
        return $out;
    }
}
