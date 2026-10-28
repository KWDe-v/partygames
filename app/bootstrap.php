<?php
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}
if (!defined('APP_VERSION')) {
    define('APP_VERSION', '4.0.0');
}

if (PHP_VERSION_ID < 70400) {
    http_response_code(503);
    exit('Este site requer PHP 7.4 ou superior.');
}

// App\X → app/X.php   |   Games\Pasta\X → games/Pasta/src/X.php
spl_autoload_register(static function (string $class): void {
    $parts = explode('\\', $class);
    if ($parts[0] === 'App') {
        $file = APP_ROOT . '/app/' . implode('/', array_slice($parts, 1)) . '.php';
    } elseif ($parts[0] === 'Games' && count($parts) > 2 && preg_match('/^[A-Za-z0-9]+$/', $parts[1])) {
        $file = APP_ROOT . '/games/' . $parts[1] . '/src/' . implode('/', array_slice($parts, 2)) . '.php';
    } else {
        return;
    }
    if (is_file($file)) {
        require $file;
    }
});

/** Escapa texto para saída em HTML. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
