<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Validação de tipos e limites dos parâmetros recebidos pela API.
 */
final class Input
{
    /** @var array<string, mixed> */
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data) && $this->data[$key] !== null && $this->data[$key] !== '';
    }

    /** Texto bruto com limite de bytes (a normalização fica a cargo de Text). */
    public function string(string $key, int $maxBytes = 2000, string $label = 'campo'): string
    {
        $value = $this->data[$key] ?? '';
        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }
        if (!is_string($value)) {
            throw new ApiException(sprintf('O %s está em um formato inválido.', $label), 422, 'invalid_input');
        }
        if (strlen($value) > $maxBytes) {
            throw new ApiException(sprintf('O %s é muito longo.', $label), 422, 'invalid_input');
        }
        return $value;
    }

    /** Lista de textos curtos (ex.: categorias marcadas). */
    public function list(string $key, int $maxItems, int $maxBytes, string $label = 'campo'): array
    {
        $value = $this->data[$key] ?? [];
        if (is_string($value)) {
            $value = [$value];
        }
        if (!is_array($value) || count($value) > $maxItems) {
            throw new ApiException(sprintf('O campo "%s" está em um formato inválido.', $label), 422, 'invalid_input');
        }
        $out = [];
        foreach ($value as $item) {
            if (!is_string($item) || strlen($item) > $maxBytes) {
                throw new ApiException(sprintf('O campo "%s" está em um formato inválido.', $label), 422, 'invalid_input');
            }
            $out[] = $item;
        }
        return $out;
    }

    /** Valor booleano enviado como true/false, 1/0 ou "true"/"false". */
    public function bool(string $key): bool
    {
        $v = $this->data[$key] ?? null;
        if (is_bool($v)) {
            return $v;
        }
        if ($v === 1 || $v === '1' || $v === 'true') {
            return true;
        }
        if ($v === 0 || $v === '0' || $v === 'false') {
            return false;
        }
        throw new ApiException('Valor inválido: escolha verdadeiro ou falso.', 422, 'invalid_input');
    }

    public function int(string $key, int $min, int $max, ?int $default = null, string $label = 'valor'): int
    {
        if (!$this->has($key)) {
            if ($default !== null) {
                return $default;
            }
            throw new ApiException(sprintf('Informe o %s.', $label), 422, 'invalid_input');
        }
        $value = $this->data[$key];
        if (is_string($value) && preg_match('/^-?\d{1,9}$/', trim($value))) {
            $value = (int) trim($value);
        }
        if (!is_int($value)) {
            throw new ApiException(sprintf('O %s deve ser um número inteiro.', $label), 422, 'invalid_input');
        }
        if ($value < $min || $value > $max) {
            throw new ApiException(sprintf('O %s deve estar entre %d e %d.', $label, $min, $max), 422, 'invalid_input');
        }
        return $value;
    }
}
