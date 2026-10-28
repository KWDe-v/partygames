<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Registro de erros em storage/logs. Os arquivos usam a extensão .log.php e começam
 * com "<?php exit; ?>", então não podem ser lidos pela web nem em servidores sem .htaccess.
 */
final class Logger
{
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function exception(\Throwable $e, string $where = ''): void
    {
        self::write('ERROR', sprintf(
            '%s%s: %s em %s:%d',
            $where !== '' ? $where . ' — ' : '',
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ), ['trace' => self::shortTrace($e)]);
    }

    private static function shortTrace(\Throwable $e): array
    {
        $out = [];
        foreach (array_slice($e->getTrace(), 0, 6) as $frame) {
            $out[] = (isset($frame['class']) ? $frame['class'] . '::' : '') . ($frame['function'] ?? '?')
                . (isset($frame['line']) ? ':' . $frame['line'] : '');
        }
        return $out;
    }

    private static function write(string $level, string $message, array $context): void
    {
        $message = (string) preg_replace('/[\r\n]+/', ' ', $message);
        $line = sprintf(
            "[%s] %s: %s%s\n",
            gmdate('Y-m-d\TH:i:s\Z'),
            $level,
            $message,
            $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) : ''
        );

        $dir = APP_ROOT . '/storage/logs';
        $file = $dir . '/app-' . gmdate('Y-m-d') . '.log.php';
        if (is_dir($dir) && is_writable($dir)) {
            $prefix = is_file($file) ? '' : "<?php exit; ?>\n";
            if (@file_put_contents($file, $prefix . $line, FILE_APPEND | LOCK_EX) !== false) {
                return;
            }
        }
        error_log('[o-que-voce-faria] ' . trim($line));
    }
}
