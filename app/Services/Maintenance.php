<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Logger;

/**
 * Limpeza executada ocasionalmente durante as requisições (sem cron):
 * - encerra salas sem nenhum jogador ativo há muito tempo;
 * - apaga salas JÁ ENCERRADAS há mais tempo que o configurado (nunca partidas em andamento);
 * - remove contadores antigos de limitação de tentativas.
 */
final class Maintenance
{
    public static function maybeRun(Database $db): void
    {
        $probability = max(0, min(100, Config::int('platform.cleanup_probability', 2)));
        if ($probability === 0 || random_int(1, 100) > $probability) {
            return;
        }
        try {
            self::run($db);
        } catch (\Throwable $e) {
            Logger::exception($e, 'Limpeza automática');
        }
    }

    public static function run(Database $db): void
    {
        $lock = 'partygames_cleanup_' . substr(md5($db->name()), 0, 16);
        if ((int) $db->fetchValue('SELECT GET_LOCK(?, 0)', [$lock]) !== 1) {
            return;
        }
        try {
            $now = time();
            $idle = $now - max(300, Config::int('platform.room_idle_expire', 1800));
            $db->execute(
                "UPDATE rooms SET status = 'finished', end_reason = 'expired', finished_at = ?,
                        state_version = state_version + 1
                  WHERE status <> 'finished' AND last_activity < ?
                    AND NOT EXISTS (SELECT 1 FROM players p
                                     WHERE p.room_id = rooms.id AND p.status = 'active' AND p.last_seen >= ?)",
                [$now, $idle, $idle]
            );
            $db->execute(
                "DELETE FROM rooms WHERE status = 'finished' AND finished_at IS NOT NULL AND finished_at < ? LIMIT 100",
                [$now - max(3600, Config::int('platform.cleanup_finished_after', 172800))]
            );
            $db->execute('DELETE FROM rate_limits WHERE window_start < ? LIMIT 1000', [$now - 86400]);
        } finally {
            $db->fetchValue('SELECT RELEASE_LOCK(?)', [$lock]);
        }
    }
}
