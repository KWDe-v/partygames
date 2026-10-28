<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

abstract class Model
{
    /** @var Database */
    protected $db;

    /** Tabela e colunas que podem ser alteradas por update(). */
    protected const TABLE = '';
    protected const UPDATABLE = [];

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM ' . static::TABLE . ' WHERE id = ?', [$id]);
    }

    public function update(int $id, array $fields): void
    {
        if (!$fields) {
            return;
        }
        $sets = [];
        $params = [];
        foreach ($fields as $column => $value) {
            if (!in_array($column, static::UPDATABLE, true)) {
                throw new \InvalidArgumentException('Coluna não permitida: ' . $column);
            }
            $sets[] = $column . ' = ?';
            $params[] = $value;
        }
        $params[] = $id;
        $this->db->execute('UPDATE ' . static::TABLE . ' SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
    }
}
