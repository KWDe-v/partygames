<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ApiException;
use App\Core\Database;
use App\Core\Input;
use App\Models\Room;
use App\Platform\RoomEngine;
use App\Services\StateBuilder;

final class GameController
{
    /** @var Database */
    private $db;
    /** @var RoomEngine */
    private $engine;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->engine = new RoomEngine($db);
    }

    /** Polling principal: processa prazos vencidos e devolve o estado (ou só a versão, se nada mudou). */
    public function state(Input $in, array $player): array
    {
        $version = $in->has('v') ? $in->int('v', 0, PHP_INT_MAX >> 1, null, 'versão') : null;
        $roomId = (int) $player['room_id'];
        $this->engine->tick($roomId);
        $room = (new Room($this->db))->find($roomId);
        if ($room === null) {
            throw new ApiException('Sala não encontrada.', 404, 'room_not_found');
        }
        return (new StateBuilder($this->db, $this->engine))->build($room, $player, $version);
    }

    public function start(Input $in, array $player): array
    {
        $this->engine->startGame($player);
        return ['started' => true];
    }

    /** Ação do jogo da sala: api/index.php?action=game&op=<ação> */
    public function action(Input $in, array $player): array
    {
        $op = $_GET['op'] ?? '';
        if (!is_string($op)) {
            throw new ApiException('Ação inválida.', 400, 'invalid_op');
        }
        return $this->engine->gameAction($player, $op, $in);
    }
}
