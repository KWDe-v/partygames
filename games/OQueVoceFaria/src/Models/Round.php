<?php
declare(strict_types=1);

namespace Games\OQueVoceFaria\Models;

use App\Models\Model;

final class Round extends Model
{
    protected const TABLE = 'oqvf_rounds';
    protected const UPDATABLE = [
        'status', 'phase_started_at', 'phase_ends_at', 'result_position', 'result_count', 'note', 'ended_at',
    ];

    public function create(int $roomId, int $sessionId, int $number, int $now, int $endsAt): int
    {
        return $this->db->insert(
            "INSERT INTO oqvf_rounds (room_id, session_id, number, status, phase_started_at, phase_ends_at, created_at)
             VALUES (?, ?, ?, 'question_writing', ?, ?, ?)",
            [$roomId, $sessionId, $number, $now, $endsAt, $now]
        );
    }
}
