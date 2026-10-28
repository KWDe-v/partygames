<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Embaralhamentos com gerador criptograficamente seguro (random_int).
 */
final class Shuffler
{
    /** Fisher–Yates. */
    public static function shuffle(array $items): array
    {
        $items = array_values($items);
        for ($i = count($items) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            $tmp = $items[$i];
            $items[$i] = $items[$j];
            $items[$j] = $tmp;
        }
        return $items;
    }

    /**
     * Desarranjo: permutação p de 0..n-1 em que p[i] !== i para todo i.
     * Primeiro tenta amostragem por rejeição (distribuição uniforme, ~e tentativas em média);
     * se o limite for atingido, usa o algoritmo de Sattolo, que sempre gera um ciclo único
     * (portanto um desarranjo válido). O resultado é sempre validado explicitamente.
     *
     * @return int[]
     */
    public static function derangement(int $n, int $maxAttempts = 100): array
    {
        if ($n < 2) {
            throw new \InvalidArgumentException('Um desarranjo exige pelo menos 2 elementos.');
        }
        $identity = range(0, $n - 1);
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $p = self::shuffle($identity);
            if (self::isDerangement($p, $n)) {
                return $p;
            }
        }
        $p = $identity;
        for ($i = $n - 1; $i > 0; $i--) {
            $j = random_int(0, $i - 1);
            $tmp = $p[$i];
            $p[$i] = $p[$j];
            $p[$j] = $tmp;
        }
        if (!self::isDerangement($p, $n)) {
            throw new \LogicException('Falha ao gerar um desarranjo válido.');
        }
        return $p;
    }

    /** Confere que $p é uma permutação de 0..n-1 sem pontos fixos. */
    public static function isDerangement(array $p, int $n): bool
    {
        if (count($p) !== $n) {
            return false;
        }
        $seen = [];
        foreach ($p as $i => $v) {
            if (!is_int($v) || $v < 0 || $v >= $n || $v === $i || isset($seen[$v])) {
                return false;
            }
            $seen[$v] = true;
        }
        return true;
    }
}
