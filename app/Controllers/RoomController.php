<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Input;
use App\Models\Room;
use App\Platform\GameRegistry;
use App\Platform\RoomEngine;
use App\Services\Auth;
use App\Services\RoomService;
use App\Services\StateBuilder;

final class RoomController
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /** Token CSRF atual e, se houver, a sala em que esta sessão está. */
    public function session(Input $in, ?array $player): array
    {
        $current = Auth::current($this->db);
        $room = $current !== null ? (new Room($this->db))->find((int) $current['room_id']) : null;
        return [
            'csrf' => Csrf::token(),
            'server_time' => StateBuilder::serverTime(),
            'room' => $room !== null ? [
                'code' => (string) $room['code'],
                'game' => (string) $room['game'],
                'status' => (string) $room['status'],
                'nickname' => (string) $current['nickname'],
            ] : null,
        ];
    }

    /** Jogos disponíveis na plataforma. */
    public function games(Input $in, ?array $player): array
    {
        $out = [];
        foreach (GameRegistry::all() as $g) {
            $out[] = [
                'slug' => $g['slug'],
                'name' => $g['name'],
                'tagline' => $g['tagline'],
                'description' => $g['description'],
                'players' => $g['players'],
                'duration' => $g['duration'],
                'cover' => $g['assets_url'] . $g['cover'],
            ];
        }
        return ['games' => $out];
    }

    public function create(Input $in, ?array $player): array
    {
        return (new RoomService($this->db))->create($in);
    }

    public function configure(Input $in, array $player): array
    {
        return (new RoomService($this->db))->configure($player, $in);
    }

    public function join(Input $in, ?array $player): array
    {
        return (new RoomService($this->db))->join($in);
    }

    public function reconnect(Input $in, ?array $player): array
    {
        return (new RoomService($this->db))->reconnect($in);
    }

    public function leave(Input $in, array $player): array
    {
        (new RoomEngine($this->db))->leave($player);
        Auth::logout();
        return ['left' => true];
    }

    public function end(Input $in, array $player): array
    {
        (new RoomEngine($this->db))->endGame($player);
        return ['ended' => true];
    }

    public function players(Input $in, array $player): array
    {
        $engine = new RoomEngine($this->db);
        $room = (new Room($this->db))->find((int) $player['room_id']);
        $state = (new StateBuilder($this->db, $engine))->build($room, $player);
        return ['version' => $state['version'], 'players' => $state['players']];
    }
}
