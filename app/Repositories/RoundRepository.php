<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\ApiException;
use App\Core\Database;
use App\Services\Shuffler;

/**
 * Rodadas genéricas (game_rounds), participantes com papéis secretos, envios e votos.
 * As restrições UNIQUE do banco impedem envios e votos duplicados mesmo com requisições simultâneas.
 */
final class RoundRepository
{
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // ------------------------------------------------------------------ rodadas

    public function create(int $sessionId, int $roomId, int $number, string $phase, int $now, ?int $endsAt, ?string $promptKey, array $public, array $secret): int
    {
        return $this->db->insert(
            'INSERT INTO game_rounds (session_id, room_id, number, phase, phase_started_at, phase_ends_at, prompt_key, public_data, secret_data, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$sessionId, $roomId, $number, $phase, $now, $endsAt, $promptKey, self::json($public), self::json($secret), $now]
        );
    }

    /** Rodada com public/secret já decodificados. */
    public function find(int $id): ?array
    {
        $r = $this->db->fetchOne('SELECT * FROM game_rounds WHERE id = ?', [$id]);
        if ($r === null) {
            return null;
        }
        $r['public'] = json_decode((string) $r['public_data'], true) ?: [];
        $r['secret'] = json_decode((string) $r['secret_data'], true) ?: [];
        return $r;
    }

    public function setPhase(int $id, string $phase, int $startedAt, ?int $endsAt): void
    {
        $this->db->execute(
            'UPDATE game_rounds SET phase = ?, phase_started_at = ?, phase_ends_at = ? WHERE id = ?',
            [$phase, $startedAt, $endsAt, $id]
        );
    }

    public function savePublic(int $id, array $public): void
    {
        $this->db->execute('UPDATE game_rounds SET public_data = ? WHERE id = ?', [self::json($public), $id]);
    }

    public function saveSecret(int $id, array $secret): void
    {
        $this->db->execute('UPDATE game_rounds SET secret_data = ? WHERE id = ?', [self::json($secret), $id]);
    }

    public function end(int $id, int $now): void
    {
        $this->db->execute('UPDATE game_rounds SET ended_at = COALESCE(ended_at, ?), phase_ends_at = NULL WHERE id = ?', [$now, $id]);
    }

    /** prompt_key já usados na partida (para não repetir perguntas/palavras/temas). */
    public function usedPromptKeys(int $sessionId): array
    {
        return array_map(static function (array $r): string {
            return (string) $r['prompt_key'];
        }, $this->db->fetchAll('SELECT prompt_key FROM game_rounds WHERE session_id = ? AND prompt_key IS NOT NULL', [$sessionId]));
    }

    // ------------------------------------------------------------------ participantes

    /** @param array<int, array{role?:string|null, secret?:array|null}> $players player_id => papel/segredo */
    public function addPlayers(int $roundId, array $players): void
    {
        foreach ($players as $pid => $info) {
            $this->db->execute(
                'INSERT IGNORE INTO game_round_players (round_id, player_id, role, secret) VALUES (?, ?, ?, ?)',
                [$roundId, (int) $pid, $info['role'] ?? null, isset($info['secret']) ? self::json($info['secret']) : null]
            );
        }
    }

    /**
     * Participantes da rodada com a situação atual na sala.
     *
     * @return array<int, array<string, mixed>> player_id => linha (secret decodificado)
     */
    public function players(int $roundId): array
    {
        $out = [];
        foreach ($this->db->fetchAll(
            'SELECT rp.player_id, rp.role, rp.secret, p.nickname, p.status
               FROM game_round_players rp JOIN players p ON p.id = rp.player_id
              WHERE rp.round_id = ? ORDER BY p.joined_at, p.id',
            [$roundId]
        ) as $r) {
            $r['secret'] = $r['secret'] !== null ? (json_decode((string) $r['secret'], true) ?: []) : [];
            $r['active'] = $r['status'] === 'active';
            $out[(int) $r['player_id']] = $r;
        }
        return $out;
    }

    // ------------------------------------------------------------------ envios

    /** Registra um envio; recusa duplicatas (garantido pela restrição UNIQUE). */
    public function addEntry(int $roundId, int $playerId, string $kind, string $body, array $meta, int $now): int
    {
        try {
            return $this->db->insert(
                'INSERT INTO game_entries (round_id, player_id, kind, body, meta, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$roundId, $playerId, $kind, $body, self::json($meta), $now]
            );
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                throw new ApiException('Você já enviou nesta etapa.', 409, 'already_submitted');
            }
            throw $e;
        }
    }

    /** @return array<int, array<string, mixed>> envios (meta decodificado), na ordem pública se houver */
    public function entries(int $roundId, string $kind): array
    {
        $rows = $this->db->fetchAll(
            'SELECT e.*, p.nickname FROM game_entries e JOIN players p ON p.id = e.player_id
              WHERE e.round_id = ? AND e.kind = ? ORDER BY COALESCE(e.position, 65535), e.id',
            [$roundId, $kind]
        );
        foreach ($rows as &$r) {
            $r['meta'] = $r['meta'] !== null ? (json_decode((string) $r['meta'], true) ?: []) : [];
        }
        return $rows;
    }

    public function entryOf(int $roundId, int $playerId, string $kind): ?array
    {
        $r = $this->db->fetchOne('SELECT * FROM game_entries WHERE round_id = ? AND player_id = ? AND kind = ?', [$roundId, $playerId, $kind]);
        if ($r !== null) {
            $r['meta'] = $r['meta'] !== null ? (json_decode((string) $r['meta'], true) ?: []) : [];
        }
        return $r;
    }

    /**
     * Embaralha os envios e atribui códigos públicos aleatórios: durante a votação o navegador
     * só conhece o código, nunca o autor.
     */
    public function assignPublicCodes(int $roundId, string $kind): void
    {
        $ids = array_map('intval', array_column($this->db->fetchAll(
            'SELECT id FROM game_entries WHERE round_id = ? AND kind = ? AND public_code IS NULL',
            [$roundId, $kind]
        ), 'id'));
        $used = [];
        foreach (Shuffler::shuffle($ids) as $position => $id) {
            do {
                $code = '';
                for ($i = 0; $i < 6; $i++) {
                    $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
                }
            } while (isset($used[$code]));
            $used[$code] = true;
            $this->db->execute('UPDATE game_entries SET public_code = ?, position = ? WHERE id = ?', [$code, $position, $id]);
        }
    }

    // ------------------------------------------------------------------ votos

    public function castVote(int $roundId, int $voterId, string $ballot, string $target, int $now): void
    {
        try {
            $this->db->insert(
                'INSERT INTO game_votes (round_id, voter_id, ballot, target, created_at) VALUES (?, ?, ?, ?, ?)',
                [$roundId, $voterId, $ballot, $target, $now]
            );
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                throw new ApiException('Seu voto já foi registrado.', 409, 'already_voted');
            }
            throw $e;
        }
    }

    /** @return array<int, array<string, mixed>> votos da rodada (uso interno no servidor) */
    public function votes(int $roundId, ?string $ballotPrefix = null): array
    {
        if ($ballotPrefix === null) {
            return $this->db->fetchAll('SELECT voter_id, ballot, target FROM game_votes WHERE round_id = ?', [$roundId]);
        }
        return $this->db->fetchAll(
            'SELECT voter_id, ballot, target FROM game_votes WHERE round_id = ? AND ballot LIKE ?',
            [$roundId, addcslashes($ballotPrefix, '%_') . '%']
        );
    }

    /** @return array<string, string> cédula => alvo, só do próprio eleitor */
    public function votesOf(int $roundId, int $voterId): array
    {
        $out = [];
        foreach ($this->db->fetchAll('SELECT ballot, target FROM game_votes WHERE round_id = ? AND voter_id = ?', [$roundId, $voterId]) as $r) {
            $out[(string) $r['ballot']] = (string) $r['target'];
        }
        return $out;
    }

    private static function json(array $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
