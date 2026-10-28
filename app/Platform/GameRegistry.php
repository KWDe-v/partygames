<?php
declare(strict_types=1);

namespace App\Platform;

use App\Core\ApiException;
use App\Core\Database;

/**
 * Catálogo de jogos instalados. Cada pasta games/<Pasta>/ com um game.php é um jogo:
 * basta copiar a pasta para a hospedagem e ele aparece na plataforma (tabelas criadas automaticamente).
 */
final class GameRegistry
{
    /** @var array<string, array<string, mixed>>|null slug => manifesto */
    private static $games;

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        if (self::$games === null) {
            self::$games = self::load();
        }
        return self::$games;
    }

    public static function has(string $slug): bool
    {
        return isset(self::all()[$slug]);
    }

    /** @return array<string, mixed> */
    public static function get(string $slug): array
    {
        $all = self::all();
        if (!isset($all[$slug])) {
            throw new ApiException('Este jogo não está disponível.', 404, 'game_not_found');
        }
        return $all[$slug];
    }

    public static function instance(string $slug, RoomEngine $engine, Database $db): AbstractGame
    {
        $manifest = self::get($slug);
        $class = $manifest['class'];
        $game = new $class(new GameContext($engine, $db, $manifest), $manifest);
        if (!$game instanceof AbstractGame) {
            throw new \LogicException('A classe do jogo deve estender AbstractGame: ' . $class);
        }
        return $game;
    }

    /** @return array<string, array<string, mixed>> */
    private static function load(): array
    {
        $games = [];
        foreach ((array) glob(APP_ROOT . '/games/*/game.php') as $file) {
            $dir = basename(dirname((string) $file));
            if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $dir)) {
                continue;
            }
            $m = require $file;
            if (!is_array($m) || ($m['enabled'] ?? true) === false) {
                continue;
            }
            foreach (['slug', 'name', 'class'] as $required) {
                if (empty($m[$required])) {
                    throw new \LogicException("Manifesto inválido em games/$dir/game.php: falta '$required'.");
                }
            }
            if (!preg_match('/^[a-z0-9][a-z0-9-]{1,39}$/', $m['slug'])) {
                throw new \LogicException("Slug inválido em games/$dir/game.php.");
            }
            $m += [
                'tagline' => '',
                'description' => '',
                'players' => [3, 20],
                'duration' => '',
                'color' => '#ff5d73',
                'cover' => 'cover.svg',
                'order' => 100,
                'tables' => [],
                'settings' => [],
                'min_players_default' => null,
                'max_players_default' => 10,
                'how_to' => [],
            ];
            $m['dir'] = $dir;
            $m['path'] = dirname((string) $file);
            $m['assets_url'] = 'games/' . $dir . '/assets/';
            $games[$m['slug']] = $m;
        }
        uasort($games, static function (array $a, array $b): int {
            return [$a['order'], $a['name']] <=> [$b['order'], $b['name']];
        });
        return $games;
    }
}
