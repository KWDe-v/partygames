<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Configuração central carregada de config/config.php, mesclada aos valores padrão.
 */
final class Config
{
    /** @var array<string, mixed> */
    private static $data = [];

    public static function load(string $file): void
    {
        if (!is_file($file)) {
            throw new SetupException(
                'O arquivo de configuração não foi encontrado. Copie config/config.example.php para '
                . 'config/config.php e informe os dados de acesso ao banco de dados.'
            );
        }
        $cfg = require $file;
        if (!is_array($cfg)) {
            throw new SetupException('O arquivo config/config.php é inválido: ele deve retornar um array.');
        }
        self::$data = array_replace_recursive(self::defaults(), $cfg);
    }

    /**
     * Lê um valor usando notação de ponto, ex.: "game.poll_interval_ms".
     *
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $path, $default = null)
    {
        $node = self::$data;
        foreach (explode('.', $path) as $key) {
            if (!is_array($node) || !array_key_exists($key, $node)) {
                return $default;
            }
            $node = $node[$key];
        }
        return $node;
    }

    public static function int(string $path, int $default = 0): int
    {
        $value = self::get($path, $default);
        return is_numeric($value) ? (int) $value : $default;
    }

    public static function bool(string $path, bool $default = false): bool
    {
        return (bool) self::get($path, $default);
    }

    /** @return array<string, mixed> */
    private static function defaults(): array
    {
        return [
            'db' => [
                'host' => 'localhost',
                'port' => 3306,
                'name' => '',
                'user' => '',
                'pass' => '',
                'charset' => 'utf8mb4',
                'auto_create_database' => false,
            ],
            'app' => [
                'base_url' => '',
                'debug' => false,
                'timezone' => 'UTC',
                'secure_cookies' => null,
                'trust_proxy_headers' => false,
                'session_lifetime' => 86400,
            ],
            // Regras comuns a todos os jogos da plataforma.
            'platform' => [
                'name' => 'PartyGames',
                'max_players_limit' => 20,
                'nickname_min_length' => 2,
                'nickname_max_length' => 20,
                'room_password_min_length' => 4,
                'room_password_max_length' => 64,
                'poll_interval_ms' => 1500,
                'offline_after' => 15,
                'abandon_after' => 180,
                'host_transfer_after' => 25,
                'room_idle_expire' => 1800,
                'cleanup_finished_after' => 172800,
                'cleanup_probability' => 2,
            ],
            // Ajustes por jogo: 'games' => ['slug-do-jogo' => ['chave' => valor]] (veja o game.php de cada jogo).
            'games' => [],
            'security' => [
                'join_max_attempts' => 8,
                'join_window' => 300,
                'join_global_max_attempts' => 30,
                'join_global_window' => 900,
                'create_max_attempts' => 10,
                'create_window' => 600,
                'reconnect_max_attempts' => 20,
                'reconnect_window' => 300,
            ],
        ];
    }
}
