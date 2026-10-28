<?php
declare(strict_types=1);

namespace App\Platform;

use App\Core\ApiException;
use App\Core\Config;
use App\Core\Database;
use App\Models\Player;
use App\Models\Room;
use App\Repositories\RoundRepository;
use App\Repositories\ScoreRepository;

/**
 * Serviços que a plataforma oferece a um jogo.
 */
final class GameContext
{
    /** @var RoomEngine */
    private $engine;
    /** @var Database */
    private $db;
    /** @var array<string, mixed> */
    private $manifest;
    /** @var Room */
    public $rooms;
    /** @var Player */
    public $players;

    public function __construct(RoomEngine $engine, Database $db, array $manifest)
    {
        $this->engine = $engine;
        $this->db = $db;
        $this->manifest = $manifest;
        $this->rooms = new Room($db);
        $this->players = new Player($db);
    }

    public function db(): Database
    {
        return $this->db;
    }

    /** Valor de config/config.php em 'games' => ['<slug>' => [...]], com padrão. */
    public function config(string $key, $default = null)
    {
        return Config::get('games.' . $this->manifest['slug'] . '.' . $key, $default);
    }

    public function configInt(string $key, int $default): int
    {
        $v = $this->config($key, $default);
        return is_numeric($v) ? (int) $v : $default;
    }

    /** Configurações escolhidas na criação da sala. */
    public function settings(array $room): array
    {
        $s = json_decode((string) ($room['settings'] ?? ''), true);
        return is_array($s) ? $s : [];
    }

    public function setting(array $room, string $key, $default = null)
    {
        return $this->settings($room)[$key] ?? $default;
    }

    public function isHost(array $room, array $player): bool
    {
        return (int) $room['host_player_id'] === (int) $player['id'];
    }

    public function assertHost(array $room, array $player): void
    {
        if (!$this->isHost($room, $player)) {
            throw new ApiException('Apenas o anfitrião pode fazer isso.', 403, 'not_host');
        }
    }

    /** Jogadores ativos e conectados agora, na ordem de entrada. */
    public function onlinePlayers(int $roomId, int $now): array
    {
        return $this->players->listOnline($roomId, $now - RoomEngine::offlineAfter());
    }

    public function activePlayers(int $roomId): array
    {
        return $this->players->listActive($roomId);
    }

    public function requiredPlayers(array $room): int
    {
        return RoomEngine::requiredPlayers($room, $this->manifest);
    }

    /**
     * Muda a fase da partida. $endsAt (timestamp) é o prazo: quando vencer, advance() é chamado.
     * $extra aceita 'round_number', 'current_round_id' e 'notice'.
     */
    public function setPhase(int $roomId, string $phase, ?int $endsAt, array $extra = []): void
    {
        $fields = ['status' => 'playing', 'phase' => $phase, 'phase_ends_at' => $endsAt];
        foreach (['round_number', 'current_round_id', 'notice'] as $k) {
            if (array_key_exists($k, $extra)) {
                $fields[$k] = $extra[$k];
            }
        }
        $this->rooms->update($roomId, $fields);
    }

    /** Volta ao lobby encerrando a partida (ex.: jogadores insuficientes); o anfitrião pode iniciar de novo. */
    public function backToLobby(int $roomId, ?string $notice): void
    {
        $room = $this->rooms->find($roomId);
        if ($room !== null) {
            $this->engine->endMatch($room, 'cancelled', time(), $notice);
        }
    }

    /**
     * Fim da partida (ex.: todas as rodadas jogadas): o placar final fica no lobby e a sala continua
     * aberta para jogar de novo ou escolher outro jogo.
     */
    public function endMatch(array $room, string $reason, int $now, ?string $notice = null): void
    {
        $this->engine->endMatch($this->fresh($room), $reason, $now, $notice);
    }

    /** Compatibilidade: encerrar = terminar a partida e voltar ao lobby. */
    public function finish(array $room, string $reason, int $now): void
    {
        $this->endMatch($room, $reason, $now);
    }

    // ------------------------------------------------------------------ pontuação e histórico

    public function sessionId(array $room): int
    {
        return (int) ($room['session_id'] ?? 0);
    }

    /**
     * Concede pontos calculados no servidor. $refKey identifica o ponto (ex.: "r3:winner"):
     * repetir a chamada nunca soma duas vezes.
     */
    public function award(array $room, int $roundNumber, int $playerId, int $points, string $reason, string $refKey): bool
    {
        if ($points === 0 || $this->sessionId($room) === 0) {
            return false;
        }
        return (new ScoreRepository($this->db))->award($this->sessionId($room), $playerId, $roundNumber, $points, $reason, $refKey, time());
    }

    /** Resumo público no histórico da partida (somente informações já reveladas). */
    public function event(array $room, int $roundNumber, string $title, string $text, string $refKey, string $type = 'round'): void
    {
        if ($this->sessionId($room) === 0) {
            return;
        }
        (new ScoreRepository($this->db))->event($this->sessionId($room), $roundNumber, $type, $refKey, ['title' => $title, 'text' => $text], time());
    }

    /** Placar atual da partida (para cálculos do jogo, ex.: vencedor final). */
    public function scoreboard(array $room): array
    {
        return (new ScoreRepository($this->db))->scoreboard($this->sessionId($room), (int) $room['id'], (int) $room['round_number']);
    }

    public function roundsRepo(): RoundRepository
    {
        return new RoundRepository($this->db);
    }

    /** Avisa todos os navegadores de que o estado mudou. Chame após cada alteração visível. */
    public function bump(int $roomId, int $now): void
    {
        $this->rooms->bump($roomId, $now);
    }

    public function fresh(array $room): array
    {
        return $this->rooms->find((int) $room['id']) ?? $room;
    }
}
