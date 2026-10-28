<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ApiException;
use App\Core\App;
use App\Core\Csrf;
use App\Core\Input;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\SetupException;
use App\Services\Auth;

/**
 * Ponto único da API JSON: api/index.php?action=...
 *
 * Toda operação que altera estado exige POST + cabeçalho X-CSRF-Token + mesma origem.
 * O jogador é identificado apenas pela sessão do servidor.
 */
final class ApiRouter
{
    /** ação => [método HTTP, controlador, método, exige jogador autenticado] */
    private const ROUTES = [
        'session'     => ['GET',  RoomController::class, 'session', false],
        'games'       => ['GET',  RoomController::class, 'games', false],
        'create_room' => ['POST', RoomController::class, 'create', false],
        'join_room'   => ['POST', RoomController::class, 'join', false],
        // Selecionar o jogo / ajustar regras no lobby (anfitrião)
        'configure_room' => ['POST', RoomController::class, 'configure', true],
        'reconnect'   => ['POST', RoomController::class, 'reconnect', false],
        'leave_room'  => ['POST', RoomController::class, 'leave', true],
        'end_game'    => ['POST', RoomController::class, 'end', true],
        'players'     => ['GET',  RoomController::class, 'players', true],
        'state'       => ['GET',  GameController::class, 'state', true],
        'start_game'  => ['POST', GameController::class, 'start', true],
        // Ações de cada jogo: action=game&op=<ação definida pelo jogo>
        'game'        => ['POST', GameController::class, 'action', true],
    ];

    public static function dispatch(): void
    {
        Response::securityHeaders();
        try {
            $db = App::boot();

            $action = $_GET['action'] ?? '';
            if (!is_string($action) || !isset(self::ROUTES[$action])) {
                throw new ApiException('Ação desconhecida.', 404, 'not_found');
            }
            [$method, $controllerClass, $handler, $needsPlayer] = self::ROUTES[$action];

            if (Request::method() !== $method) {
                header('Allow: ' . $method);
                throw new ApiException('Método não permitido.', 405, 'method_not_allowed');
            }

            if ($method === 'POST') {
                if (!Request::isSameOrigin()) {
                    throw new ApiException('Origem da requisição não permitida.', 403, 'bad_origin');
                }
                if (!Csrf::verify(Request::header('X-CSRF-Token'))) {
                    throw new ApiException('Sua sessão expirou. Tente novamente.', 403, 'csrf');
                }
                $input = new Input(Request::json());
            } else {
                $input = new Input($_GET);
            }

            $player = null;
            if ($needsPlayer) {
                $player = Auth::current($db);
                if ($player === null) {
                    throw new ApiException('Você não está em uma sala.', 401, 'not_in_room');
                }
                Auth::touch($db, $player);
            }

            // Leituras não alteram a sessão: liberamos o bloqueio do arquivo de sessão cedo.
            if ($method === 'GET' && $action !== 'session') {
                Session::release();
            }

            $controller = new $controllerClass($db);
            $data = $controller->{$handler}($input, $player);
            Response::json(['ok' => true, 'data' => $data]);
        } catch (ApiException $e) {
            Response::error($e->getMessage(), $e->status(), $e->errorCode());
        } catch (SetupException $e) {
            Response::error($e->getMessage(), 503, 'setup');
        } catch (\Throwable $e) {
            Logger::exception($e, 'API');
            Response::error('Ocorreu um erro interno. Tente novamente em instantes.', 500, 'server_error');
        }
    }
}
