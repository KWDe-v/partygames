<?php
declare(strict_types=1);

namespace Games\OQueVoceFaria\Models;

use App\Models\Model;

final class Result extends Model
{
    protected const TABLE = 'oqvf_results';

    /** @param array<int, array{question_id:int, answer_id:int}> $items na ordem de apresentação */
    public function insertSequence(int $roundId, array $items): void
    {
        foreach (array_values($items) as $position => $item) {
            $this->db->insert(
                'INSERT INTO oqvf_results (round_id, position, question_id, answer_id) VALUES (?, ?, ?, ?)',
                [$roundId, $position, $item['question_id'], $item['answer_id']]
            );
        }
    }

    /** Textos do resultado em uma posição — sem nenhuma informação de autoria. */
    public function at(int $roundId, int $position): ?array
    {
        return $this->db->fetchOne(
            'SELECT r.position, q.body AS question, a.body AS answer
               FROM oqvf_results r
               JOIN oqvf_questions q ON q.id = r.question_id
               JOIN oqvf_answers a ON a.id = r.answer_id
              WHERE r.round_id = ? AND r.position = ?',
            [$roundId, $position]
        );
    }
}
