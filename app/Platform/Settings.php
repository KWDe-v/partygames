<?php
declare(strict_types=1);

namespace App\Platform;

use App\Core\ApiException;
use App\Core\Config;
use App\Core\Input;
use App\Core\Text;

/**
 * Configurações de sala declaradas no manifesto de cada jogo ('settings').
 *
 * Tipos de campo:
 *   int    → ['label', 'min', 'max', 'default', 'unit'?, 'zero'?, 'hint'?, 'summary'?]
 *   select → ['label', 'options' => [valor => rótulo], 'default', 'summary'?]
 *   bool   → ['label', 'default' => true|false, 'summary'?]
 * O padrão pode ser alterado em config/config.php: 'games' => ['<slug>' => ['<campo>' => valor]].
 */
final class Settings
{
    public static function fields(array $manifest): array
    {
        $out = [];
        foreach ((array) $manifest['settings'] as $key => $f) {
            if (!preg_match('/^[a-z][a-z0-9_]{0,39}$/', (string) $key)) {
                continue;
            }
            $f += ['type' => 'int', 'label' => $key, 'summary' => $f['label'] ?? $key, 'hint' => ''];
            $override = Config::get('games.' . $manifest['slug'] . '.' . $key);
            if ($override !== null && self::isValid($f, $override)) {
                $f['default'] = $override;
            }
            $out[(string) $key] = $f;
        }
        return $out;
    }

    /** Valida os valores enviados na criação da sala (ausentes = padrão). */
    public static function validate(array $manifest, Input $in): array
    {
        $values = [];
        foreach (self::fields($manifest) as $key => $f) {
            switch ($f['type']) {
                case 'select':
                    $v = $in->has($key) ? $in->string($key, 100, $f['label']) : (string) $f['default'];
                    if (!array_key_exists($v, (array) $f['options'])) {
                        throw new ApiException(sprintf('Opção inválida em "%s".', $f['label']), 422, 'invalid_input');
                    }
                    $values[$key] = $v;
                    break;
                case 'bool':
                    $raw = $in->has($key) ? $in->string($key, 10, $f['label']) : ($f['default'] ? '1' : '0');
                    $values[$key] = in_array(strtolower($raw), ['1', 'true', 'on', 'sim'], true);
                    break;
                case 'multi':
                    // Várias opções (ex.: categorias). Pelo menos 'min' marcadas.
                    $chosen = $in->has($key) ? $in->list($key, count((array) $f['options']), 60, $f['label']) : (array) $f['default'];
                    $chosen = array_values(array_unique(array_filter($chosen, static function ($v) use ($f): bool {
                        return array_key_exists((string) $v, (array) $f['options']);
                    })));
                    if (count($chosen) < (int) ($f['min'] ?? 1)) {
                        throw new ApiException(sprintf('Escolha pelo menos %d opção(ões) em "%s".', (int) ($f['min'] ?? 1), $f['label']), 422, 'invalid_input');
                    }
                    $values[$key] = $chosen;
                    break;
                case 'lines':
                    // Lista livre, uma por linha (ex.: perguntas personalizadas).
                    $raw = $in->has($key) ? $in->string($key, 20000, $f['label']) : '';
                    $lines = [];
                    foreach (preg_split('/\R/u', $raw) ?: [] as $line) {
                        $line = Text::normalize($line);
                        if ($line === '') {
                            continue;
                        }
                        $len = Text::length($line);
                        if ($len < (int) $f['min_len'] || $len > (int) $f['max_len']) {
                            throw new ApiException(sprintf('Cada linha de "%s" deve ter entre %d e %d caracteres.', $f['label'], $f['min_len'], $f['max_len']), 422, 'invalid_input');
                        }
                        $lines[] = $line;
                    }
                    $lines = array_values(array_unique($lines));
                    if (count($lines) > (int) $f['max_items']) {
                        throw new ApiException(sprintf('"%s" aceita no máximo %d linhas.', $f['label'], $f['max_items']), 422, 'invalid_input');
                    }
                    $values[$key] = $lines;
                    break;
                default:
                    $values[$key] = $in->int($key, (int) $f['min'], (int) $f['max'], (int) $f['default'], 'campo "' . $f['label'] . '"');
            }
        }
        return $values;
    }

    /** Linhas [rótulo, valor] para o resumo exibido no lobby. */
    public static function summary(array $manifest, array $values): array
    {
        $rows = [];
        foreach (self::fields($manifest) as $key => $f) {
            if (!array_key_exists($key, $values)) {
                continue;
            }
            $v = $values[$key];
            if ($f['type'] === 'lines') {
                if (!$v) {
                    continue;
                }
                $text = count($v) === 1 ? '1 personalizada' : count($v) . ' personalizadas';
            } elseif ($f['type'] === 'multi') {
                $labels = [];
                foreach ((array) $v as $opt) {
                    $labels[] = (string) ($f['options'][$opt] ?? $opt);
                }
                $text = !$labels ? 'nenhuma' : (count($labels) === count((array) $f['options']) ? 'todas' : implode(', ', $labels));
            } elseif ($f['type'] === 'select') {
                $text = (string) ($f['options'][$v] ?? $v);
            } elseif ($f['type'] === 'bool') {
                $text = $v ? 'sim' : 'não';
            } elseif ((int) $v === 0 && isset($f['zero'])) {
                $text = (string) $f['zero'];
            } else {
                $text = $v . ($f['unit'] ?? '');
            }
            $rows[] = [(string) $f['summary'], $text];
        }
        return $rows;
    }

    private static function isValid(array $f, $v): bool
    {
        switch ($f['type']) {
            case 'select':
                return array_key_exists((string) $v, (array) $f['options']);
            case 'bool':
                return is_bool($v);
            case 'multi':
                return is_array($v) && $v && !array_diff($v, array_keys((array) $f['options']));
            case 'lines':
                return is_array($v);
            default:
                return is_int($v) && $v >= (int) $f['min'] && $v <= (int) $f['max'];
        }
    }
}
