<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    private const MAX_BODY_BYTES = 32768;

    public static function method(): string
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
            return true;
        }
        return Config::bool('app.trust_proxy_headers')
            && strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    public static function ip(): string
    {
        if (Config::bool('app.trust_proxy_headers')) {
            foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'] as $h) {
                if (!empty($_SERVER[$h])) {
                    $candidate = trim(explode(',', (string) $_SERVER[$h])[0]);
                    if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                        return $candidate;
                    }
                }
            }
        }
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    public static function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return isset($_SERVER[$key]) ? (string) $_SERVER[$key] : null;
    }

    /** Caminho base da aplicação (ex.: "/" ou "/jogo/"), válido para index.php e api/. */
    public static function basePath(): string
    {
        $dir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php')));
        if (substr($dir, -4) === '/api') {
            $dir = substr($dir, 0, -4);
        }
        return rtrim($dir, '/') . '/';
    }

    /**
     * Rejeita requisições de outra origem (defesa adicional ao token CSRF).
     */
    public static function isSameOrigin(): bool
    {
        $origin = self::header('Origin');
        if ($origin === null || $origin === '' || $origin === 'null') {
            return $origin !== 'null';
        }
        $host = parse_url($origin, PHP_URL_HOST);
        $port = parse_url($origin, PHP_URL_PORT);
        $originHost = strtolower((string) $host) . ($port ? ':' . $port : '');
        $serverHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        // Compara ignorando portas padrão.
        $strip = static function (string $h): string {
            return (string) preg_replace('/:(80|443)$/', '', $h);
        };
        return $strip($originHost) === $strip($serverHost);
    }

    /** @return array<string, mixed> */
    public static function json(): array
    {
        $raw = file_get_contents('php://input', false, null, 0, self::MAX_BODY_BYTES + 1);
        if ($raw === false || $raw === '') {
            return [];
        }
        if (strlen($raw) > self::MAX_BODY_BYTES) {
            throw new ApiException('Requisição muito grande.', 413, 'payload_too_large');
        }
        $data = json_decode($raw, true, 8);
        if (!is_array($data)) {
            throw new ApiException('Dados inválidos.', 400, 'invalid_json');
        }
        return $data;
    }
}
