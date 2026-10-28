<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Core\Database;

/**
 * Limitação de tentativas por janela de tempo. As chaves (que contêm IPs) são gravadas como HMAC.
 */
final class RateLimiter
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    private function key(string $bucket): string
    {
        return hash_hmac('sha256', $bucket, AppMeta::secret($this->db));
    }

    public function tooMany(string $bucket, int $max, int $window): bool
    {
        $row = $this->db->fetchOne('SELECT hits, window_start FROM rate_limits WHERE bucket = ?', [$this->key($bucket)]);
        return $row !== null && (int) $row['window_start'] > time() - $window && (int) $row['hits'] >= $max;
    }

    public function assertAllowed(string $bucket, int $max, int $window): void
    {
        if ($this->tooMany($bucket, $max, $window)) {
            throw new ApiException('Muitas tentativas em pouco tempo. Aguarde alguns minutos e tente novamente.', 429, 'rate_limited');
        }
    }

    public function hit(string $bucket, int $window): void
    {
        $now = time();
        $expired = $now - $window;
        $this->db->execute(
            'INSERT INTO rate_limits (bucket, hits, window_start) VALUES (?, 1, ?)
             ON DUPLICATE KEY UPDATE hits = IF(window_start <= ?, 1, hits + 1),
                                     window_start = IF(window_start <= ?, ?, window_start)',
            [$this->key($bucket), $now, $expired, $expired, $now]
        );
    }

    public function clear(string $bucket): void
    {
        $this->db->execute('DELETE FROM rate_limits WHERE bucket = ?', [$this->key($bucket)]);
    }
}
