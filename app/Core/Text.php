<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Normalização e validação de textos enviados pelos jogadores.
 */
final class Text
{
    public static function normalize(string $s): string
    {
        if ($s !== '' && !preg_match('//u', $s)) {
            throw new ApiException('O texto contém caracteres inválidos.', 422, 'invalid_text');
        }
        if (class_exists('Normalizer')) {
            $normalized = \Normalizer::normalize($s, \Normalizer::FORM_C);
            if (is_string($normalized)) {
                $s = $normalized;
            }
        }
        // Remove caracteres de controle, de largura zero e de controle bidirecional.
        $s = (string) preg_replace('/[\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}\x{00AD}\x{200B}-\x{200F}\x{2028}-\x{202E}\x{2060}-\x{2064}\x{FEFF}]/u', '', $s);
        $s = (string) preg_replace('/\s+/u', ' ', $s);
        return trim($s);
    }

    /** Corta em $max caracteres (UTF-8), sem depender da extensão mbstring. */
    public static function truncate(string $s, int $max): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($s, 0, $max, 'UTF-8');
        }
        return preg_match('/^.{0,' . $max . '}/us', $s, $m) ? $m[0] : substr($s, 0, $max);
    }

    /** Forma comparável: minúsculas, sem acentos e só letras/números (ex.: "Pão-de-Queijo" → "paodequeijo"). */
    public static function fold(string $s): string
    {
        $s = strtr($s, [
            'Á' => 'a', 'À' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Ä' => 'a', 'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'É' => 'e', 'Ê' => 'e', 'È' => 'e', 'Ë' => 'e', 'é' => 'e', 'ê' => 'e', 'è' => 'e', 'ë' => 'e',
            'Í' => 'i', 'Ì' => 'i', 'Î' => 'i', 'Ï' => 'i', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'Ó' => 'o', 'Ò' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'Ú' => 'u', 'Ù' => 'u', 'Û' => 'u', 'Ü' => 'u', 'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ç' => 'c', 'ç' => 'c', 'Ñ' => 'n', 'ñ' => 'n',
        ]);
        return (string) preg_replace('/[^a-z0-9]/', '', strtolower($s));
    }

    public static function length(string $s): int
    {
        return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : (int) preg_match_all('/./us', $s);
    }

    /**
     * Texto livre de um jogo (pergunta, resposta, palpite...): normalizado e com limites de tamanho.
     * $what é o sujeito usado nas mensagens, ex.: "A resposta".
     */
    public static function limited(string $raw, int $min, int $max, string $what): string
    {
        $s = self::normalize($raw);
        self::requireLength($s, $min, $max, $what);
        return $s;
    }

    public static function requireLength(string $s, int $min, int $max, string $what): void
    {
        $len = self::length($s);
        if ($len === 0) {
            throw new ApiException(sprintf('%s não pode ficar em branco.', $what), 422, 'empty_text');
        }
        if ($len < $min || $len > $max) {
            throw new ApiException(sprintf('%s deve ter entre %d e %d caracteres.', $what, $min, $max), 422, 'invalid_length');
        }
        if (!preg_match('/[\p{L}\p{N}]/u', $s)) {
            throw new ApiException(sprintf('%s precisa conter letras ou números.', $what), 422, 'invalid_text');
        }
    }

    public static function nickname(string $raw): string
    {
        $s = self::normalize($raw);
        self::requireLength(
            $s,
            Config::int('platform.nickname_min_length', 2),
            Config::int('platform.nickname_max_length', 20),
            'O apelido'
        );
        if (!preg_match('/^[\p{L}\p{M}\p{N} _.\'!-]+$/u', $s)) {
            throw new ApiException('O apelido pode conter apenas letras, números, espaços e . _ - \' !', 422, 'invalid_nickname');
        }
        return $s;
    }

    public static function roomCode(string $raw): string
    {
        $code = strtoupper((string) preg_replace('/[\s-]+/', '', $raw));
        if (!preg_match('/^[A-Z0-9]{6}$/', $code)) {
            throw new ApiException('O código da sala deve ter 6 letras ou números.', 422, 'invalid_code');
        }
        return $code;
    }
}
