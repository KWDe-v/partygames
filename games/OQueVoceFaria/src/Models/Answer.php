<?php
declare(strict_types=1);

namespace Games\OQueVoceFaria\Models;

use App\Models\Model;

final class Answer extends Model
{
    protected const TABLE = 'oqvf_answers';
    protected const UPDATABLE = ['status'];

    public function create(int $questionId, int $roundId, int $playerId, string $body, int $now): int
    {
        return $this->db->insert(
            "INSERT INTO oqvf_answers (question_id, round_id, player_id, body, status, submitted_at) VALUES (?, ?, ?, ?, 'valid', ?)",
            [$questionId, $roundId, $playerId, $body, $now]
        );
    }

    public function findByPlayer(int $roundId, int $playerId): ?array
    {
        return $this->db->fetchOne('SELECT * FROM oqvf_answers WHERE round_id = ? AND player_id = ?', [$roundId, $playerId]);
    }

    /**
     * Pares originais válidos (pergunta respondida + sua resposta).
     *
     * @return array<int, array{question_id:int, answer_id:int}>
     */
    public function validPairs(int $roundId): array
    {
        return $this->db->fetchAll(
            "SELECT q.id AS question_id, a.id AS answer_id
               FROM oqvf_questions q JOIN oqvf_answers a ON a.question_id = q.id
              WHERE q.round_id = ? AND q.status = 'answered' AND a.status = 'valid'
              ORDER BY q.id",
            [$roundId]
        );
    }
}
