<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Configurações internas geradas na inicialização (tabela app_meta).
 */
final class AppMeta
{
    /** @var string|null */
    private static $secret;

    /** Segredo aleatório da instalação, usado em HMACs (ex.: chaves de limitação por IP). */
    public static function secret(Database $db): string
    {
        if (self::$secret === null) {
            $value = $db->fetchValue("SELECT meta_value FROM app_meta WHERE meta_key = 'app_secret'");
            if (!is_string($value) || strlen($value) < 32) {
                $value = bin2hex(random_bytes(32));
                $db->execute("INSERT IGNORE INTO app_meta (meta_key, meta_value) VALUES ('app_secret', ?)", [$value]);
                $value = (string) $db->fetchValue("SELECT meta_value FROM app_meta WHERE meta_key = 'app_secret'");
            }
            self::$secret = $value;
        }
        return self::$secret;
    }
}
