<?php
declare(strict_types=1);

namespace App\Core;

use App\Services\Maintenance;

/**
 * Inicialização: configuração → tratamento de erros → sessão → banco → migrações automáticas.
 */
final class App
{
    /** @var Database|null */
    private static $db;

    public static function boot(): Database
    {
        if (self::$db !== null) {
            return self::$db;
        }
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        error_reporting(E_ALL);

        Config::load(APP_ROOT . '/config/config.php');

        $tz = (string) Config::get('app.timezone', 'UTC');
        date_default_timezone_set(in_array($tz, timezone_identifiers_list(), true) ? $tz : 'UTC');

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        Session::start();
        $db = new Database((array) Config::get('db', []));
        (new Migrator($db))->migrate();
        Maintenance::maybeRun($db);

        self::$db = $db;
        return $db;
    }
}
