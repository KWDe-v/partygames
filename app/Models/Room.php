<?php
declare(strict_types=1);

namespace App\Models;

final class Room extends Model
{
    protected const TABLE = 'rooms';
    protected const UPDATABLE = [
        'host_player_id', 'status', 'phase', 'phase_ends_at', 'current_round_id', 'round_number', 'session_id',
        'game', 'settings', 'min_players', 'max_players',
        'notice', 'end_reason', 'sweep_at', 'last_activity', 'finished_at',
    ];

    public function findByCode(string $code): ?array
    {
        return $this->db->fetchOne('SELECT * FROM rooms WHERE code = ?', [$code]);
    }

    /** Bloqueia a linha da sala até o fim da transação: serializa todas as mudanças de estado. */
    public function lock(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM rooms WHERE id = ? FOR UPDATE', [$id]);
    }

    public function codeExists(string $code): bool
    {
        return $this->db->fetchValue('SELECT 1 FROM rooms WHERE code = ?', [$code]) !== null;
    }

    public function create(array $d): int
    {
        return $this->db->insert(
            "INSERT INTO rooms (code, name, game, password_hash, status, min_players, max_players, settings, created_at, last_activity, sweep_at)
             VALUES (?, ?, ?, ?, 'lobby', ?, ?, ?, ?, ?, ?)",
            [
                $d['code'], $d['name'], $d['game'], $d['password_hash'], $d['min_players'], $d['max_players'],
                json_encode($d['settings'], JSON_UNESCAPED_UNICODE), $d['now'], $d['now'], $d['now'] + 5,
            ]
        );
    }

    /** Incrementa a versão do estado: os clientes só recebem dados completos quando ela muda. */
    public function bump(int $id, int $now): void
    {
        $this->db->execute(
            'UPDATE rooms SET state_version = state_version + 1, last_activity = ? WHERE id = ?',
            [$now, $id]
        );
    }
}
