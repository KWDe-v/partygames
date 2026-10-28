<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\SetupException;
use App\Platform\GameRegistry;
use App\Platform\Settings;

/**
 * Renderiza a aplicação de página única ou uma página de erro amigável de inicialização.
 */
final class PageController
{
    public static function handle(): void
    {
        Response::securityHeaders();
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
        try {
            App::boot();
            $cap = max(2, min(50, Config::int('platform.max_players_limit', 20)));
            $games = [];
            foreach (GameRegistry::all() as $slug => $g) {
                $min = max(2, (int) $g['players'][0]);
                $max = max($min, min($cap, (int) $g['players'][1]));
                $games[$slug] = $g + [
                    'fields' => Settings::fields($g),
                    'pmin' => $min,
                    'pmax' => $max,
                    'pmin_default' => max($min, (int) ($g['min_players_default'] ?? $min)),
                    'pmax_default' => min($max, (int) $g['max_players_default']),
                ];
            }
            $platform = (string) Config::get('platform.name', 'PartyGames');
            self::render('app', [
                'csrf' => Csrf::token(),
                'platform' => $platform,
                'games' => $games,
                'poll' => max(800, min(5000, Config::int('platform.poll_interval_ms', 1500))),
                'nmax' => Config::int('platform.nickname_max_length', 20),
                'pmin' => Config::int('platform.room_password_min_length', 4),
                'pmaxlen' => Config::int('platform.room_password_max_length', 64),
                'baseUrl' => self::baseUrl(),
                'description' => $platform . ': jogos de festa online pra jogar com os amigos, cada um no seu celular. Crie uma sala, mande o código e comece. Grátis, sem instalar nada.',
            ]);
        } catch (SetupException $e) {
            http_response_code(503);
            self::render('error', ['message' => $e->getMessage(), 'detail' => null]);
        } catch (\Throwable $e) {
            Logger::exception($e, 'Página');
            http_response_code(500);
            $detail = Config::bool('app.debug') ? get_class($e) . ': ' . $e->getMessage() : null;
            self::render('error', ['message' => 'Ocorreu um erro inesperado ao carregar a página.', 'detail' => $detail]);
        }
    }

    /** URL absoluta da aplicação (as redes sociais exigem og:image absoluta). */
    private static function baseUrl(): string
    {
        $configured = (string) Config::get('app.base_url', '');
        if ($configured !== '') {
            return rtrim($configured, '/') . '/';
        }
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        if (!preg_match('/^[A-Za-z0-9.\-]+(:\d{1,5})?$/', $host)) {
            $host = 'localhost';
        }
        return (Request::isHttps() ? 'https' : 'http') . '://' . $host . Request::basePath();
    }

    private static function render(string $view, array $vars): void
    {
        extract($vars, EXTR_SKIP);
        require APP_ROOT . '/views/' . $view . '.php';
    }
}
