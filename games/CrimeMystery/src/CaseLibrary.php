<?php
declare(strict_types=1);

namespace Games\CrimeMystery;

use App\Core\Logger;

/**
 * Carrega os casos de games/CrimeMystery/cases/*.php. Casos inválidos são ignorados e registrados no log.
 */
final class CaseLibrary
{
    /** @var array<string, array>|null */
    private static $cases;

    /** @return array<string, array> id => caso */
    public static function all(): array
    {
        if (self::$cases === null) {
            self::$cases = [];
            foreach ((array) glob(dirname(__DIR__) . '/cases/*.php') as $file) {
                $case = require $file;
                $errors = is_array($case) ? CaseValidator::validate($case) : ['arquivo não retorna um array'];
                if ($errors) {
                    Logger::error('Caso inválido ignorado: ' . basename((string) $file), ['erros' => $errors]);
                    continue;
                }
                self::$cases[$case['id']] = $case;
            }
            uasort(self::$cases, static function (array $a, array $b): int {
                return strcmp($a['title'], $b['title']);
            });
        }
        return self::$cases;
    }

    public static function get(string $id): ?array
    {
        return self::all()[$id] ?? null;
    }

    /** Opções do formulário de criação de sala. */
    public static function options(): array
    {
        $opts = ['random' => 'Sorteado'];
        foreach (self::all() as $id => $c) {
            $opts[$id] = $c['title'] . ' (' . $c['difficulty'] . ')';
        }
        return $opts;
    }
}
