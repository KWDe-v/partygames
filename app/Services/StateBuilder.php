<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Player;
use App\Platform\GameRegistry;
use App\Platform\RoomEngine;
use App\Platform\Settings;
use App\Repositories\ScoreRepository;
use App\Repositories\SessionRepository;

/**
 * Estado que cada jogador vê: dados da sala (plataforma) + visão do jogo (módulo do jogo).
 */
final class StateBuilder
{
    private const END_MESSAGES = [
        'host_ended' => 'O anfitrião encerrou a partida.',
        'max_rounds' => 'Todas as rodadas foram jogadas!',
        'game_over' => 'Fim de jogo!',
        'expired' => 'A sala foi encerrada por inatividade.',
        'empty' => 'Todos os jogadores saíram da sala.',
    ];

    /** @var Database */
    private $db;
    /** @var RoomEngine */
    private $engine;

    public function __construct(Database $db, RoomEngine $engine)
    {
        $this->db = $db;
        $this->engine = $engine;
    }

    public static function serverTime(): int
    {
        return (int) round(microtime(true) * 1000);
    }

    /** Estado completo, ou apenas {changed:false} se a versão conhecida pelo cliente ainda é a atual. */
    public function build(array $room, array $player, ?int $clientVersion = null): array
    {
        $version = (int) $room['state_version'];
        $base = ['version' => $version, 'server_time' => self::serverTime()];
        if ($clientVersion !== null && $clientVersion === $version) {
            return $base + ['changed' => false];
        }

        $manifest = GameRegistry::get((string) $room['game']);
        $pid = (int) $player['id'];
        $isHost = (int) $room['host_player_id'] === $pid;
        $status = (string) $room['status'];
        $active = (new Player($this->db))->listActive((int) $room['id']);

        $gameView = $status === 'playing' ? $this->engine->game($room)->view($room, $player, $active) : [];
        $progress = (array) ($gameView['progress'] ?? []);
        unset($gameView['progress']);

        $players = [];
        $online = 0;
        foreach ($active as $p) {
            $isOnline = $p['connection'] === 'online';
            $online += $isOnline ? 1 : 0;
            $players[] = [
                'id' => (int) $p['id'],
                'nickname' => (string) $p['nickname'],
                'host' => (int) $p['id'] === (int) $room['host_player_id'],
                'online' => $isOnline,
                'me' => (int) $p['id'] === $pid,
                'progress' => $progress[(int) $p['id']] ?? null,
            ];
        }

        $settings = json_decode((string) $room['settings'], true) ?: [];
        $need = RoomEngine::requiredPlayers($room, $manifest);

        // Placar e histórico da partida atual (ou da última, quando a sala volta ao lobby).
        $match = null;
        $scoreboard = [];
        $history = [];
        $sessionId = (int) $room['session_id'];
        $session = $sessionId > 0 ? (new SessionRepository($this->db))->find($sessionId) : null;
        if ($session !== null) {
            $scores = new ScoreRepository($this->db);
            $sessionGame = GameRegistry::has((string) $session['game']) ? GameRegistry::get((string) $session['game']) : $manifest;
            $match = [
                'number' => (int) $session['number'],
                'status' => (string) $session['status'],
                'game_name' => (string) $sessionGame['name'],
                'scoring' => ($sessionGame['scoring'] ?? true) !== false,
            ];
            $scoreboard = $scores->scoreboard($sessionId, (int) $room['id'], $status === 'playing' ? (int) $room['round_number'] : -1);
            $history = $scores->history($sessionId, 30);
        }

        return $base + [
            'changed' => true,
            'match' => $match,
            'scoreboard' => $scoreboard,
            'history' => $history,
            'room' => [
                'code' => (string) $room['code'],
                'name' => (string) ($room['name'] ?? ''),
                'game' => (string) $room['game'],
                'game_name' => (string) $manifest['name'],
                'game_cover' => $manifest['assets_url'] . $manifest['cover'],
                'status' => $status,
                'phase' => $room['phase'],
                'phase_ends_at' => $room['phase_ends_at'] !== null ? (int) $room['phase_ends_at'] * 1000 : null,
                'has_password' => $room['password_hash'] !== null,
                'round_number' => (int) $room['round_number'],
                'notice' => $room['notice'],
                'end_reason' => $room['end_reason'],
                'end_message' => $status === 'finished' ? (self::END_MESSAGES[(string) $room['end_reason']] ?? 'A partida foi encerrada.') : null,
                'required_players' => $need,
                'online_players' => $online,
                'min_players' => (int) $room['min_players'],
                'max_players' => (int) $room['max_players'],
                'can_start' => $isHost && $status === 'lobby' && $online >= $need,
                'settings' => $settings,
                'settings_summary' => array_merge(
                    [['Jogadores', $room['min_players'] . ' a ' . $room['max_players']]],
                    Settings::summary($manifest, $settings)
                ),
            ],
            'me' => ['id' => $pid, 'nickname' => (string) $player['nickname'], 'host' => $isHost],
            'players' => $players,
            'game' => $gameView ?: null,
        ];
    }
}
