<?php
declare(strict_types=1);

namespace App\Core;

use App\Platform\GameRegistry;

/**
 * Inicialização automática e migrações versionadas, por módulo.
 *
 * Módulos: "platform" (database/migrations) e um por jogo ("game:<slug>", games/<Pasta>/migrations).
 * Cada um tem sua própria sequência de versões em schema_version, então instalar um jogo novo
 * só executa as migrações dele.
 *
 * - Arquivos NNN_nome.php retornam ['name' => ..., 'up' => callable(Database)].
 * - Migrações devem ser idempotentes (CREATE TABLE IF NOT EXISTS, INSERT IGNORE...).
 * - GET_LOCK impede que duas requisições migrem ao mesmo tempo. Nunca há DROP TABLE.
 */
final class Migrator
{
    private const BASE_TABLES = [
        'schema_version', 'migration_log', 'app_meta', 'rooms', 'players', 'rate_limits', 'game_sessions',
        'score_entries', 'game_events', 'game_rounds', 'game_round_players', 'game_entries', 'game_votes',
    ];

    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function migrate(): void
    {
        $modules = $this->modules();
        $latest = [];
        foreach ($modules as $module => $info) {
            $latest[$module] = $info['files'] ? max(array_keys($info['files'])) : 0;
        }
        if (!$modules['platform']['files']) {
            throw new SetupException('Arquivos de migração do banco não encontrados. Envie novamente a pasta database/.');
        }

        $signature = 'schema_ok_' . md5(json_encode($latest));
        $current = $this->currentVersions();
        $pending = false;
        foreach ($latest as $module => $version) {
            if (($current[$module] ?? 0) < $version) {
                $pending = true;
            }
        }
        $needsRepair = false;
        if (!$pending) {
            // Confere a existência das tabelas uma vez por sessão.
            if (!empty($_SESSION[$signature])) {
                return;
            }
            $needsRepair = $this->missingTables($modules) !== [];
            if (!$needsRepair) {
                $_SESSION[$signature] = true;
                return;
            }
        }

        $lock = 'partygames_schema_' . substr(md5($this->db->name()), 0, 16);
        if ((int) $this->db->fetchValue('SELECT GET_LOCK(?, 25)', [$lock]) !== 1) {
            throw new SetupException('O banco de dados está sendo preparado por outra requisição. Atualize a página em alguns segundos.');
        }
        try {
            $this->ensureBaseTables();
            $current = $this->currentVersions();
            foreach ($modules as $module => $info) {
                foreach ($info['files'] as $version => $file) {
                    $done = $version <= ($current[$module] ?? 0);
                    if ($done && !$needsRepair) {
                        continue;
                    }
                    $this->runMigration($module, $version, $file, $done);
                }
            }
            $missing = $this->missingTables($modules);
            if ($missing) {
                Logger::error('Tabelas ausentes após a migração', ['tables' => $missing]);
                throw new SetupException('Não foi possível criar todas as tabelas. Verifique se o usuário do banco tem permissão de CREATE, ALTER, INDEX e REFERENCES.');
            }
            $_SESSION[$signature] = true;
        } finally {
            try {
                $this->db->fetchValue('SELECT RELEASE_LOCK(?)', [$lock]);
            } catch (\Throwable $ignored) {
            }
        }
    }

    /** @return array<string, array{files: array<int, string>, tables: string[]}> plataforma primeiro */
    private function modules(): array
    {
        $modules = ['platform' => ['files' => $this->files(APP_ROOT . '/database/migrations'), 'tables' => self::BASE_TABLES]];
        foreach (GameRegistry::all() as $slug => $game) {
            $modules['game:' . $slug] = [
                'files' => $this->files($game['path'] . '/migrations'),
                'tables' => array_values((array) $game['tables']),
            ];
        }
        return $modules;
    }

    private function runMigration(string $module, int $version, string $file, bool $isRepair): void
    {
        $definition = require $file;
        if (!is_array($definition) || !isset($definition['up']) || !is_callable($definition['up'])) {
            throw new SetupException('Arquivo de migração inválido: ' . basename($file));
        }
        $name = (string) ($definition['name'] ?? basename($file, '.php'));
        $start = microtime(true);
        try {
            ($definition['up'])($this->db);
        } catch (\Throwable $e) {
            Logger::exception($e, "Migração $module #$version");
            $this->log($module, $version, $name, 'failed', $e instanceof \mysqli_sql_exception ? 'código ' . $e->getCode() : get_class($e));
            if ($e instanceof \mysqli_sql_exception && in_array($e->getCode(), [1044, 1142], true)) {
                throw new SetupException('O usuário do banco de dados não tem permissão para criar as tabelas (CREATE/ALTER/INDEX/REFERENCES).');
            }
            throw new SetupException("Falha ao atualizar a estrutura do banco de dados ($module, versão $version). Os detalhes foram registrados no log do servidor.");
        }
        $ms = (int) round((microtime(true) - $start) * 1000);
        $this->db->execute(
            'INSERT IGNORE INTO schema_version (module, version, name, execution_ms) VALUES (?, ?, ?, ?)',
            [$module, $version, $name, $ms]
        );
        $this->log($module, $version, $name, $isRepair ? 'repaired' : 'applied', null);
        Logger::info(sprintf('Migração %s #%d (%s) %s em %d ms.', $module, $version, $name, $isRepair ? 'reaplicada' : 'aplicada', $ms));
    }

    private function log(string $module, int $version, string $name, string $status, ?string $message): void
    {
        try {
            $this->db->execute(
                'INSERT INTO migration_log (module, version, name, status, message) VALUES (?, ?, ?, ?, ?)',
                [$module, $version, $name, $status, $message]
            );
        } catch (\Throwable $ignored) {
        }
    }

    private function ensureBaseTables(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS schema_version (
            module VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            version INT UNSIGNED NOT NULL,
            name VARCHAR(190) NOT NULL,
            execution_ms INT UNSIGNED NOT NULL DEFAULT 0,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (module, version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->exec("CREATE TABLE IF NOT EXISTS migration_log (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            module VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            version INT UNSIGNED NOT NULL,
            name VARCHAR(190) NOT NULL,
            status ENUM('applied','repaired','failed') NOT NULL,
            message VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_migration_log_module (module, version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /** @return array<string, int> */
    private function currentVersions(): array
    {
        try {
            $out = [];
            foreach ($this->db->fetchAll('SELECT module, MAX(version) AS v FROM schema_version GROUP BY module') as $row) {
                $out[(string) $row['module']] = (int) $row['v'];
            }
            return $out;
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() === 1146) {
                return []; // primeira execução
            }
            throw $e;
        }
    }

    /** @return string[] */
    private function missingTables(array $modules): array
    {
        $existing = [];
        foreach ($this->db->fetchAll('SHOW TABLES') as $row) {
            $existing[strtolower((string) reset($row))] = true;
        }
        $missing = [];
        foreach ($modules as $info) {
            foreach ($info['tables'] as $t) {
                if (!isset($existing[strtolower($t)])) {
                    $missing[] = $t;
                }
            }
        }
        return $missing;
    }

    /** @return array<int, string> versão => caminho */
    private function files(string $dir): array
    {
        $files = [];
        foreach ((array) glob($dir . '/*.php') as $path) {
            if (preg_match('/^(\d{3,})_[a-z0-9_]+\.php$/', basename((string) $path), $m)) {
                $files[(int) $m[1]] = (string) $path;
            }
        }
        ksort($files);
        return $files;
    }
}
