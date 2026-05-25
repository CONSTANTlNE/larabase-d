<?php

namespace App\Services\Drivers;

use App\Models\Connection;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

class PostgresDriver implements DatabaseDriverInterface
{
    private ?PDO $pdo = null;

    /** @var array<int, array{schema: string, name: string}>|null */
    private ?array $tablesCache = null;

    /** @var array<string, array<int, array<string, mixed>>> */
    private array $columnsCache = [];

    public function __construct(private readonly Connection $connection) {}

    public function connect(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $this->connection->host,
            $this->connection->port,
            $this->connection->database,
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        $this->pdo = new PDO(
            $dsn,
            $this->connection->username,
            decrypt($this->connection->password),
            $options,
        );

        return $this->pdo;
    }

    /**
     * @return array<int, array{schema: string, name: string}>
     */
    public function getTables(): array
    {
        if ($this->tablesCache !== null) {
            return $this->tablesCache;
        }

        $stmt = $this->connect()->query(
            "SELECT table_schema AS schema, table_name AS name
             FROM information_schema.tables
             WHERE table_schema NOT IN ('information_schema', 'pg_catalog', 'pg_toast')
             AND table_type = 'BASE TABLE'
             ORDER BY table_schema, table_name ASC"
        );

        $this->tablesCache = $stmt->fetchAll();

        return $this->tablesCache;
    }

    /**
     * @return array<int, array{column_name: string, data_type: string, is_nullable: string, column_default: string|null}>
     */
    public function getColumns(string $qualifiedName): array
    {
        if (isset($this->columnsCache[$qualifiedName])) {
            return $this->columnsCache[$qualifiedName];
        }

        [$schema, $table] = $this->validateTableName($qualifiedName);

        $stmt = $this->connect()->prepare(
            'SELECT column_name, data_type, is_nullable, column_default
             FROM information_schema.columns
             WHERE table_schema = ? AND table_name = ?
             ORDER BY ordinal_position'
        );
        $stmt->execute([$schema, $table]);

        return $this->columnsCache[$qualifiedName] = $stmt->fetchAll();
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function getRows(string $qualifiedName, int $page = 1, int $perPage = 50, ?string $sortCol = null, string $sortDir = 'ASC', ?string $searchCol = null, ?string $searchVal = null, string $searchOp = 'contains'): array
    {
        [$schema, $table] = $this->validateTableName($qualifiedName);
        $offset = ($page - 1) * $perPage;

        [$where, $whereParams] = $this->buildSearchWhere($qualifiedName, $searchCol, $searchVal, $searchOp);

        $countStmt = $this->connect()->prepare(
            "SELECT COUNT(*) FROM \"{$schema}\".\"{$table}\"{$where}"
        );
        $countStmt->execute($whereParams);
        $total = (int) $countStmt->fetchColumn();

        $orderBy = '';
        if ($sortCol !== null) {
            $validatedCol = $this->validateColumnName($qualifiedName, $sortCol);
            $direction = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';
            $orderBy = " ORDER BY \"{$validatedCol}\" {$direction}";
        }

        $stmt = $this->connect()->prepare(
            "SELECT * FROM \"{$schema}\".\"{$table}\"{$where}{$orderBy} LIMIT ? OFFSET ?"
        );
        $stmt->execute([...$whereParams, $perPage, $offset]);

        return [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
        ];
    }

    /**
     * @return array{columns: array, indexes: array, foreign_keys: array}
     */
    public function getTableStructure(string $qualifiedName): array
    {
        [$schema, $table] = $this->validateTableName($qualifiedName);
        $pdo = $this->connect();

        $colStmt = $pdo->prepare(
            'SELECT column_name, data_type, is_nullable, column_default
             FROM information_schema.columns
             WHERE table_schema = ? AND table_name = ?
             ORDER BY ordinal_position'
        );
        $colStmt->execute([$schema, $table]);
        $columns = $colStmt->fetchAll();

        $idxStmt = $pdo->prepare(
            "SELECT i.relname AS index_name, ix.indisunique AS is_unique,
                    ix.indisprimary AS is_primary,
                    string_agg(a.attname, ', ' ORDER BY k.pos) AS columns
             FROM pg_class t
             JOIN pg_index ix ON t.oid = ix.indrelid
             JOIN pg_class i ON i.oid = ix.indexrelid
             JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(ix.indkey)
             JOIN unnest(ix.indkey) WITH ORDINALITY k(attnum, pos) ON a.attnum = k.attnum
             JOIN pg_namespace ns ON t.relnamespace = ns.oid
             WHERE t.relname = ? AND t.relkind = 'r' AND ns.nspname = ?
             GROUP BY i.relname, ix.indisunique, ix.indisprimary"
        );
        $idxStmt->execute([$table, $schema]);
        $indexes = $idxStmt->fetchAll();

        $fkStmt = $pdo->prepare(
            "SELECT tc.constraint_name, kcu.column_name,
                    ccu.table_name AS foreign_table, ccu.column_name AS foreign_column
             FROM information_schema.table_constraints tc
             JOIN information_schema.key_column_usage kcu
               ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
             JOIN information_schema.constraint_column_usage ccu
               ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema
             WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_name = ? AND tc.table_schema = ?"
        );
        $fkStmt->execute([$table, $schema]);
        $foreignKeys = $fkStmt->fetchAll();

        return [
            'columns' => $columns,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
        ];
    }

    /**
     * @return array{columns: list<string>, rows: list<array<string, mixed>>, duration_ms: int, affected: int, error: string|null}
     */
    public function executeQuery(string $sql): array
    {
        $start = microtime(true);

        try {
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();

            $durationMs = (int) round((microtime(true) - $start) * 1000);
            $rows = $stmt->fetchAll() ?: [];
            $columns = $rows ? array_keys($rows[0]) : [];

            return [
                'columns' => $columns,
                'rows' => $rows,
                'duration_ms' => $durationMs,
                'affected' => $stmt->rowCount(),
                'error' => null,
            ];
        } catch (Throwable $e) {
            $durationMs = (int) round((microtime(true) - $start) * 1000);

            return [
                'columns' => [],
                'rows' => [],
                'duration_ms' => $durationMs,
                'affected' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        try {
            $this->pdo = null;
            $this->tablesCache = null;
            $this->connect()->query('SELECT 1');

            return ['success' => true, 'message' => 'Connection successful'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getTableCount(string $qualifiedName): int
    {
        [$schema, $table] = $this->validateTableName($qualifiedName);
        $stmt = $this->connect()->prepare(
            "SELECT COUNT(*) FROM \"{$schema}\".\"{$table}\""
        );
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return string[]
     */
    public function getPrimaryKeyColumns(string $qualifiedName): array
    {
        [$schema, $table] = $this->validateTableName($qualifiedName);

        $stmt = $this->connect()->prepare(
            'SELECT a.attname
             FROM pg_class t
             JOIN pg_index ix ON t.oid = ix.indrelid
             JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(ix.indkey)
             JOIN pg_namespace ns ON t.relnamespace = ns.oid
             WHERE t.relname = ? AND ns.nspname = ? AND ix.indisprimary = true
             ORDER BY a.attnum'
        );
        $stmt->execute([$table, $schema]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @param  array<string, mixed>  $pkValues
     */
    public function deleteRow(string $qualifiedName, array $pkValues): void
    {
        [$schema, $table] = $this->validateTableName($qualifiedName);
        $pkColumns = $this->getPrimaryKeyColumns($qualifiedName);

        if (empty($pkColumns)) {
            throw new RuntimeException('Table has no primary key — cannot safely delete rows.');
        }

        $whereClauses = array_map(fn (string $col) => "\"{$col}\" = ?", $pkColumns);
        $sql = "DELETE FROM \"{$schema}\".\"{$table}\" WHERE ".implode(' AND ', $whereClauses);

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute(array_map(fn (string $col) => $pkValues[$col] ?? null, $pkColumns));
    }

    /**
     * @param  array<string, mixed>  $pkValues
     * @param  array<string, mixed>  $newValues
     */
    public function updateRow(string $qualifiedName, array $pkValues, array $newValues): void
    {
        [$schema, $table] = $this->validateTableName($qualifiedName);
        $pkColumns = $this->getPrimaryKeyColumns($qualifiedName);

        if (empty($pkColumns)) {
            throw new RuntimeException('Table has no primary key — cannot safely update rows.');
        }

        $allowedColumns = array_column($this->getColumns($qualifiedName), 'column_name');
        $setClauses = [];
        $setValues = [];

        foreach ($newValues as $col => $val) {
            if (! in_array($col, $allowedColumns, true)) {
                throw new RuntimeException("Invalid column: {$col}");
            }
            $setClauses[] = "\"{$col}\" = ?";
            $setValues[] = $val;
        }

        if (empty($setClauses)) {
            throw new RuntimeException('No values to update.');
        }

        $whereClauses = array_map(fn (string $col) => "\"{$col}\" = ?", $pkColumns);
        $whereValues = array_map(fn (string $col) => $pkValues[$col] ?? null, $pkColumns);

        $sql = "UPDATE \"{$schema}\".\"{$table}\" SET ".implode(', ', $setClauses)
            .' WHERE '.implode(' AND ', $whereClauses);

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([...$setValues, ...$whereValues]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $pkValueSets
     */
    public function deleteRows(string $qualifiedName, array $pkValueSets): int
    {
        [$schema, $table] = $this->validateTableName($qualifiedName);
        $pkColumns = $this->getPrimaryKeyColumns($qualifiedName);

        if (empty($pkColumns)) {
            throw new RuntimeException('Table has no primary key — cannot safely delete rows.');
        }

        $pdo = $this->connect();
        $whereClauses = array_map(fn (string $col) => "\"{$col}\" = ?", $pkColumns);
        $sql = "DELETE FROM \"{$schema}\".\"{$table}\" WHERE ".implode(' AND ', $whereClauses);

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare($sql);
            $deleted = 0;

            foreach ($pkValueSets as $pkValues) {
                $stmt->execute(array_map(fn (string $col) => $pkValues[$col] ?? null, $pkColumns));
                $deleted += $stmt->rowCount();
            }

            $pdo->commit();

            return $deleted;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function truncateTable(string $qualifiedName): void
    {
        [$schema, $table] = $this->validateTableName($qualifiedName);
        $this->connect()->exec("TRUNCATE TABLE \"{$schema}\".\"{$table}\"");
    }

    public function dropTable(string $qualifiedName): void
    {
        [$schema, $table] = $this->validateTableName($qualifiedName);
        $this->connect()->exec("DROP TABLE \"{$schema}\".\"{$table}\"");
        $this->tablesCache = null;
    }

    /**
     * Returns the PostgreSQL data_type for a column, e.g. "jsonb", "integer", "text".
     */
    private function getColumnType(string $qualifiedName, string $column): string
    {
        foreach ($this->getColumns($qualifiedName) as $col) {
            if ($col['column_name'] === $column) {
                return $col['data_type'];
            }
        }

        return 'text';
    }

    /**
     * Builds a safe WHERE clause string + bound parameter array for a column search.
     *
     * @return array{string, array<int, mixed>}
     */
    private function buildSearchWhere(string $qualifiedName, ?string $searchCol, ?string $searchVal, string $searchOp): array
    {
        if ($searchCol === null || $searchVal === null || $searchVal === '') {
            return ['', []];
        }

        $col = $this->validateColumnName($qualifiedName, $searchCol);
        $type = $this->getColumnType($qualifiedName, $col);

        $numericTypes = ['integer', 'bigint', 'smallint', 'serial', 'bigserial',
            'numeric', 'decimal', 'real', 'double precision', 'money'];

        return match (true) {
            // JSONB: native containment operator
            $type === 'jsonb' && $searchOp === 'jsonb_contains' => [
                " WHERE \"{$col}\" @> ?::jsonb", [$searchVal],
            ],
            // JSONB: full-text search by casting to text
            $type === 'jsonb' => [
                " WHERE \"{$col}\"::text ILIKE ?", ['%'.$searchVal.'%'],
            ],
            // Boolean: coerce input to boolean literal
            $type === 'boolean' => [
                " WHERE \"{$col}\" = ?::boolean",
                [in_array(strtolower($searchVal), ['true', '1', 'yes', 't', 'on']) ? 'true' : 'false'],
            ],
            // Numeric: exact match via text cast (preserves index use for simple cases)
            in_array($type, $numericTypes) => [
                " WHERE \"{$col}\"::text LIKE ?", [$searchVal.'%'],
            ],
            // Text — starts with
            $searchOp === 'starts_with' => [
                " WHERE \"{$col}\" ILIKE ?", [$searchVal.'%'],
            ],
            // Text — exact
            $searchOp === 'equals' => [
                " WHERE \"{$col}\" = ?", [$searchVal],
            ],
            // Default: case-insensitive contains
            default => [
                " WHERE \"{$col}\" ILIKE ?", ['%'.$searchVal.'%'],
            ],
        };
    }

    /**
     * Accepts "schema.table" or bare "table" (matched against any schema).
     * Returns [schema, table].
     *
     * @return array{string, string}
     *
     * @throws RuntimeException
     */
    private function validateTableName(string $qualifiedName): array
    {
        if (str_contains($qualifiedName, '.')) {
            [$schema, $table] = explode('.', $qualifiedName, 2);
        } else {
            $schema = null;
            $table = $qualifiedName;
        }

        foreach ($this->getTables() as $row) {
            if ($row['name'] === $table && ($schema === null || $row['schema'] === $schema)) {
                return [$row['schema'], $row['name']];
            }
        }

        throw new RuntimeException("Invalid table: {$qualifiedName}");
    }

    /** @throws RuntimeException */
    private function validateColumnName(string $qualifiedName, string $column): string
    {
        $columns = array_column($this->getColumns($qualifiedName), 'column_name');
        if (! in_array($column, $columns, true)) {
            throw new RuntimeException("Invalid column: {$column}");
        }

        return $column;
    }
}
