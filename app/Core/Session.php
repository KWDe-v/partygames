<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Sessão nativa do PHP com cookie seguro (HttpOnly, SameSite, Secure em HTTPS, modo estrito).
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure = Config::get('app.secure_cookies');
        $secure = $secure === null ? Request::isHttps() : (bool) $secure;

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');
        ini_set('session.gc_maxlifetime', (string) Config::int('app.session_lifetime', 86400));

        session_name('OQVFSESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => Request::basePath(),
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        if (!@session_start()) {
            throw new SetupException('Não foi possível iniciar a sessão do PHP nesta hospedagem.');
        }
        if (empty($_SESSION['identity']) || !is_string($_SESSION['identity'])) {
            $_SESSION['identity'] = bin2hex(random_bytes(32));
        }
    }

    /** Novo identificador de sessão após autenticação (evita fixação de sessão). */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /** Libera o bloqueio do arquivo de sessão para não serializar requisições de leitura. */
    public static function release(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    public static function identity(): string
    {
        return (string) ($_SESSION['identity'] ?? '');
    }
}
