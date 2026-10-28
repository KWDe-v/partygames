<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Camada fina sobre MySQLi: prepared statements, transações com nova tentativa em deadlock.
 */
final class Database
{
    /** @var \mysqli */
    private $conn;
    /** @var int */
    private $depth = 0;
    /** @var string */
    private $name;

    public function __construct(array $cfg)
    {
        if (!extension_loaded('mysqli')) {
            throw new SetupException('A extensão MySQLi do PHP não está habilitada nesta hospedagem.');
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $host = (string) ($cfg['host'] ?? 'localhost');
        $port = (int) ($cfg['port'] ?? 3306);
        $user = (string) ($cfg['user'] ?? '');
        $pass = (string) ($cfg['pass'] ?? '');
        $name = (string) ($cfg['name'] ?? '');
        $charset = (string) ($cfg['charset'] ?? 'utf8mb4');

        if ($name === '' || $user === '' || $name === 'nome_do_banco') {
            throw new SetupException('O banco de dados ainda não foi configurado. Informe host, usuário, senha e nome do banco em config/config.php.');
        }
        if (!preg_match('/^[A-Za-z0-9_$-]{1,64}$/', $name)) {
            throw new SetupException('O nome do banco de dados em config/config.php contém caracteres inválidos.');
        }
        $this->name = $name;

        try {
            $this->conn = new \mysqli($host, $user, $pass, $name, $port);
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() === 1049 && !empty($cfg['auto_create_database'])) {
                $this->conn = $this->connectAndCreate($host, $user, $pass, $name, $port);
            } else {
                Logger::error('Falha ao conectar ao banco de dados', ['code' => $e->getCode()]);
                throw new SetupException(self::friendlyConnectError($e->getCode()));
            }
        }

        try {
            $this->conn->set_charset($charset);
            $this->conn->query("SET time_zone = '+00:00'");
            $this->conn->query('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
            $this->conn->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
        } catch (\mysqli_sql_exception $e) {
            Logger::exception($e, 'Configuração da conexão');
            throw new SetupException('Não foi possível preparar a conexão com o banco de dados (verifique se o charset utf8mb4 é suportado).');
        }
    }

    private function connectAndCreate(string $host, string $user, string $pass, string $name, int $port): \mysqli
    {
        try {
            $conn = new \mysqli($host, $user, $pass, '', $port);
            $conn->query('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $conn->select_db($name);
            Logger::info('Banco de dados criado automaticamente.');
            return $conn;
        } catch (\mysqli_sql_exception $e) {
            Logger::error('Falha ao criar o banco de dados', ['code' => $e->getCode()]);
            throw new SetupException('O banco de dados informado não existe e não pôde ser criado automaticamente. Crie-o pelo painel da hospedagem.');
        }
    }

    private static function friendlyConnectError(int $code): string
    {
        switch ($code) {
            case 1045:
            case 1044:
                return 'Acesso negado ao banco de dados. Confira o usuário, a senha e as permissões em config/config.php.';
            case 1049:
                return 'O banco de dados informado em config/config.php não existe. Crie-o pelo painel da hospedagem.';
            case 2002:
            case 2003:
            case 2005:
            case 2006:
                return 'Não foi possível conectar ao servidor MySQL. Verifique o host e a porta em config/config.php e se o serviço está ativo.';
            case 1040:
                return 'O servidor de banco de dados está sobrecarregado no momento. Tente novamente em instantes.';
            default:
                return 'Não foi possível conectar ao banco de dados. Verifique as configurações em config/config.php.';
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    /** Executa uma instrução com parâmetros vinculados. */
    public function query(string $sql, array $params = []): \mysqli_stmt
    {
        $stmt = $this->conn->prepare($sql);
        if ($params) {
            $types = '';
            $values = [];
            foreach ($params as $p) {
                if (is_int($p) || is_bool($p)) {
                    $types .= 'i';
                    $values[] = (int) $p;
                } elseif (is_float($p)) {
                    $types .= 'd';
                    $values[] = $p;
                } elseif ($p === null) {
                    $types .= 's';
                    $values[] = null;
                } else {
                    $types .= 's';
                    $values[] = (string) $p;
                }
            }
            $stmt->bind_param($types, ...$values);
        }
        $stmt->execute();
        return $stmt;
    }

    /** @return array<int, array<string, mixed>> */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $rows = $this->fetchAll($sql, $params);
        return $rows[0] ?? null;
    }

    /** @return mixed */
    public function fetchValue(string $sql, array $params = [])
    {
        $row = $this->fetchOne($sql, $params);
        return $row === null ? null : reset($row);
    }

    /** Executa UPDATE/DELETE e retorna o número de linhas afetadas. */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->query($sql, $params);
        $affected = $stmt->affected_rows;
        $stmt->close();
        return (int) $affected;
    }

    public function insert(string $sql, array $params = []): int
    {
        $stmt = $this->query($sql, $params);
        $id = $stmt->insert_id;
        $stmt->close();
        return (int) $id;
    }

    /** Instrução sem parâmetros (DDL das migrações). */
    public function exec(string $sql): void
    {
        $this->conn->query($sql);
    }

    public function inTransaction(): bool
    {
        return $this->depth > 0;
    }

    /**
     * Executa $fn dentro de uma transação. Deadlocks e timeouts de bloqueio são repetidos.
     *
     * @return mixed
     */
    public function transaction(callable $fn)
    {
        if ($this->depth > 0) {
            return $fn($this);
        }
        $attempt = 0;
        while (true) {
            $attempt++;
            $this->conn->begin_transaction();
            $this->depth = 1;
            try {
                $result = $fn($this);
                $this->conn->commit();
                $this->depth = 0;
                return $result;
            } catch (\Throwable $e) {
                $this->depth = 0;
                try {
                    $this->conn->rollback();
                } catch (\Throwable $ignored) {
                }
                if ($e instanceof \mysqli_sql_exception && in_array($e->getCode(), [1213, 1205], true) && $attempt < 3) {
                    usleep(random_int(20000, 90000));
                    continue;
                }
                throw $e;
            }
        }
    }
}
