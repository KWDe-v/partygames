<?php
declare(strict_types=1);

namespace Games\OQueVoceFaria\Models;

use App\Models\Model;

final class Question extends Model
{
    protected const TABLE = 'oqvf_questions';
    protected const UPDATABLE = ['recipient_id', 'status'];

    public function create(int $roundId, int $authorId, string $body, int $now): int
    {
        return $this->db->insert(
            "INSERT INTO oqvf_questions (round_id, author_id, body, status, submitted_at) VALUES (?, ?, ?, 'submitted', ?)",
            [$roundId, $authorId, $body, $now]
        );
    }

    public function findByAuthor(int $roundId, int $authorId): ?array
    {
        return $this->db->fetchOne('SELECT * FROM oqvf_questions WHERE round_id = ? AND author_id = ?', [$roundId, $authorId]);
    }

    public function findForRecipient(int $roundId, int $recipientId): ?array
    {
        return $this->db->fetchOne('SELECT * FROM oqvf_questions WHERE round_id = ? AND recipient_id = ?', [$roundId, $recipientId]);
    }

    /** Perguntas enviadas na rodada, com a situação atual do autor. */
    public function submittedWithAuthorStatus(int $roundId): array
    {
        return $this->db->fetchAll(
            "SELECT q.id, q.author_id, p.status AS author_status
               FROM oqvf_questions q JOIN players p ON p.id = q.author_id
              WHERE q.round_id = ? AND q.status = 'submitted'
              ORDER BY q.id",
            [$roundId]
        );
    }

    public function markRoundStatus(int $roundId, string $from, string $to): int
    {
        return $this->db->execute('UPDATE oqvf_questions SET status = ? WHERE round_id = ? AND status = ?', [$to, $roundId, $from]);
    }
}
