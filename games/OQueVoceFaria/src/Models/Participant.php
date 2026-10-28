<?php
declare(strict_types=1);

namespace Games\OQueVoceFaria\Models;

use App\Models\Model;

/**
 * Quem foi convocado para cada rodada e o andamento de cada etapa.
 */
final class Participant extends Model
{
    protected const TABLE = 'oqvf_participants';
    private const STATUS_COLUMNS = ['question_status', 'answer_status'];

    /** @param int[] $playerIds */
    public function addMany(int $roundId, array $playerIds): void
    {
        foreach ($playerIds as $pid) {
            $this->db->execute('INSERT IGNORE INTO oqvf_participants (round_id, player_id) VALUES (?, ?)', [$roundId, (int) $pid]);
        }
    }

    public function get(int $roundId, int $playerId): ?array
    {
        return $this->db->fetchOne('SELECT * FROM oqvf_participants WHERE round_id = ? AND player_id = ?', [$roundId, $playerId]);
    }

    /** @return array<int, array<string, mixed>> indexado por player_id */
    public function forRound(int $roundId): array
    {
        $out = [];
        foreach ($this->db->fetchAll('SELECT * FROM oqvf_participants WHERE round_id = ?', [$roundId]) as $row) {
            $out[(int) $row['player_id']] = $row;
        }
        return $out;
    }

    public function countByStatus(int $roundId, string $column, string $status): int
    {
        $this->assertColumn($column);
        return (int) $this->db->fetchValue("SELECT COUNT(*) FROM oqvf_participants WHERE round_id = ? AND $column = ?", [$roundId, $status]);
    }

    public function setStatus(int $roundId, int $playerId, string $column, string $status): void
    {
        $this->assertColumn($column);
        $this->db->execute("UPDATE oqvf_participants SET $column = ? WHERE round_id = ? AND player_id = ?", [$status, $roundId, $playerId]);
    }

    /** Quem não concluiu até o fim da etapa é marcado como "missed". */
    public function markPendingAsMissed(int $roundId, string $column, ?int $playerId = null): int
    {
        $this->assertColumn($column);
        $sql = "UPDATE oqvf_participants SET $column = 'missed' WHERE round_id = ? AND $column = 'pending'";
        $params = [$roundId];
        if ($playerId !== null) {
            $sql .= ' AND player_id = ?';
            $params[] = $playerId;
        }
        return $this->db->execute($sql, $params);
    }

    private function assertColumn(string $column): void
    {
        if (!in_array($column, self::STATUS_COLUMNS, true)) {
            throw new \InvalidArgumentException('Coluna inválida: ' . $column);
        }
    }
}
