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
     * @param  array<int, array{col: string, val: string, op: string}>  $filters
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function getRows(string $qualifiedName, int $page = 1, int $perPage = 50, ?string $sortCol = null, string $sortDir = 'ASC', array $filters = []): array
    {
        [$schema, $table] = $this->validateTableName($qualifiedName);
        $offset = ($page - 1) * $perPage;

        [$where, $whereParams] = $this->buildSearchWhere($qualifiedName, $filters);

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
     * @return array{
     *   outgoing: array<int, array{constraint_name: string, columns: string[], foreign_schema: string, foreign_table: string, foreign_columns: string[], delete_rule: string, update_rule: string}>,
     *   incoming: array<int, array{constraint_name: string, referencing_schema: string, referencing_table: string, referencing_columns: string[], columns: string[], delete_rule: string, update_rule: string}>
     * }
     */
    public function getRelations(string $qualifiedName): array
    {
        [$schema, $table] = $this->validateTableName($qualifiedName);
        $pdo = $this->connect();

        // Outgoing: FK constraints ON this table pointing to other tables
        $outStmt = $pdo->prepare(
            'SELECT tc.constraint_name, kcu.column_name,
                    ccu.table_schema AS foreign_schema, ccu.table_name AS foreign_table,
                    ccu.column_name AS foreign_column,
                    rc.update_rule, rc.delete_rule
             FROM information_schema.table_constraints tc
             JOIN information_schema.key_column_usage kcu
               ON tc.constraint_name = kcu.constraint_name
              AND tc.table_schema = kcu.table_schema
              AND tc.table_name = kcu.table_name
             JOIN information_schema.constraint_column_usage ccu
               ON ccu.constraint_name = tc.constraint_name
              AND ccu.table_schema = tc.table_schema
             JOIN information_schema.referential_constraints rc
               ON rc.constraint_name = tc.constraint_name
              AND rc.constraint_schema = tc.table_schema
             WHERE tc.constraint_type = \'FOREIGN KEY\'
               AND tc.table_schema = ? AND tc.table_name = ?
             ORDER BY tc.constraint_name, kcu.ordinal_position'
        );
        $outStmt->execute([$schema, $table]);

        $outgoing = [];
        foreach ($outStmt->fetchAll() as $row) {
            $name = $row['constraint_name'];
            if (! isset($outgoing[$name])) {
                $outgoing[$name] = [
                    'constraint_name' => $name,
                    'columns' => [],
                    'foreign_schema' => $row['foreign_schema'],
                    'foreign_table' => $row['foreign_table'],
                    'foreign_columns' => [],
                    'delete_rule' => $row['delete_rule'],
                    'update_rule' => $row['update_rule'],
                ];
            }
            $outgoing[$name]['columns'][] = $row['column_name'];
            $outgoing[$name]['foreign_columns'][] = $row['foreign_column'];
        }

        // Incoming: FK constraints on OTHER tables that reference this table
        $inStmt = $pdo->prepare(
            'SELECT tc.constraint_name,
                    tc.table_schema AS referencing_schema, tc.table_name AS referencing_table,
                    kcu.column_name AS referencing_column, ccu.column_name AS referenced_column,
                    rc.update_rule, rc.delete_rule
             FROM information_schema.table_constraints tc
             JOIN information_schema.key_column_usage kcu
               ON tc.constraint_name = kcu.constraint_name
              AND tc.table_schema = kcu.table_schema
              AND tc.table_name = kcu.table_name
             JOIN information_schema.constraint_column_usage ccu
               ON ccu.constraint_name = tc.constraint_name
              AND ccu.table_schema = tc.table_schema
             JOIN information_schema.referential_constraints rc
               ON rc.constraint_name = tc.constraint_name
              AND rc.constraint_schema = tc.table_schema
             WHERE tc.constraint_type = \'FOREIGN KEY\'
               AND ccu.table_schema = ? AND ccu.table_name = ?
             ORDER BY tc.constraint_name, kcu.ordinal_position'
        );
        $inStmt->execute([$schema, $table]);

        $incoming = [];
        foreach ($inStmt->fetchAll() as $row) {
            $name = $row['constraint_name'];
            if (! isset($incoming[$name])) {
                $incoming[$name] = [
                    'constraint_name' => $name,
                    'referencing_schema' => $row['referencing_schema'],
                    'referencing_table' => $row['referencing_table'],
                    'referencing_columns' => [],
                    'columns' => [],
                    'delete_rule' => $row['delete_rule'],
                    'update_rule' => $row['update_rule'],
                ];
            }
            $incoming[$name]['referencing_columns'][] = $row['referencing_column'];
            $incoming[$name]['columns'][] = $row['referenced_column'];
        }

        return [
            'outgoing' => array_values($outgoing),
            'incoming' => array_values($incoming),
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public function getColumnEnums(string $qualifiedName): array
    {
        [$schema, $table] = $this->validateTableName($qualifiedName);

        $stmt = $this->connect()->prepare(
            'SELECT a.attname AS column_name, e.enumlabel AS enum_value
             FROM pg_attribute a
             JOIN pg_class c ON c.oid = a.attrelid
             JOIN pg_namespace n ON n.oid = c.relnamespace
             JOIN pg_type t ON t.oid = a.atttypid
             JOIN pg_enum e ON e.enumtypid = t.oid
             WHERE n.nspname = ? AND c.relname = ?
             ORDER BY a.attnum, e.enumsortorder'
        );
        $stmt->execute([$schema, $table]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['column_name']][] = $row['enum_value'];
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function explainQuery(string $sql): array
    {
        // Strip any leading EXPLAIN ... prefix the user may have typed
        $clean = preg_replace('/^\s*EXPLAIN\s*(\([^)]*\)\s*)?/i', '', trim($sql));

        $stmt = $this->connect()->prepare("EXPLAIN (FORMAT JSON) {$clean}");
        $stmt->execute();
        $row = $stmt->fetch();
        $decoded = json_decode((string) ($row['QUERY PLAN'] ?? '[]'), true);

        return $decoded[0]['Plan'] ?? [];
    }

    /**
     * @return array{available: bool, rows: list<array<string, mixed>>}
     */
    public function getPgStatStatements(int $limit = 50): array
    {
        $pdo = $this->connect();

        $check = $pdo->query("SELECT 1 FROM pg_extension WHERE extname = 'pg_stat_statements'");
        if (! $check->fetchColumn()) {
            return ['available' => false, 'rows' => []];
        }

        $stmt = $pdo->prepare(
            'SELECT query,
                    calls,
                    round(mean_exec_time::numeric, 2) AS mean_ms,
                    round(total_exec_time::numeric, 2) AS total_ms,
                    rows,
                    round(stddev_exec_time::numeric, 2) AS stddev_ms,
                    round(
                        (100.0 * shared_blks_hit /
                         NULLIF(shared_blks_hit + shared_blks_read, 0))::numeric, 1
                    ) AS cache_hit_pct
             FROM pg_stat_statements
             ORDER BY mean_exec_time DESC
             LIMIT ?'
        );
        $stmt->execute([$limit]);

        return ['available' => true, 'rows' => $stmt->fetchAll()];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getTableBloat(): array
    {
        $stmt = $this->connect()->query(
            "SELECT schemaname,
                    relname AS table_name,
                    n_live_tup,
                    n_dead_tup,
                    last_vacuum,
                    last_autovacuum,
                    last_analyze,
                    last_autoanalyze,
                    pg_size_pretty(
                        pg_total_relation_size(
                            quote_ident(schemaname) || '.' || quote_ident(relname)
                        )
                    ) AS total_size,
                    CASE WHEN (n_live_tup + n_dead_tup) > 0
                         THEN round(100.0 * n_dead_tup / (n_live_tup + n_dead_tup), 1)
                         ELSE 0
                    END AS bloat_pct
             FROM pg_stat_user_tables
             WHERE schemaname NOT IN ('pg_catalog', 'information_schema')
             ORDER BY n_dead_tup DESC"
        );

        return $stmt->fetchAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getExtensions(): array
    {
        $stmt = $this->connect()->query(
            'SELECT
                 ae.name,
                 ae.default_version,
                 ae.installed_version,
                 ae.comment,
                 CASE WHEN ae.installed_version IS NOT NULL THEN 1 ELSE 0 END AS is_installed,
                 n.nspname AS schema_name
             FROM pg_available_extensions ae
             LEFT JOIN pg_extension e ON e.extname = ae.name
             LEFT JOIN pg_namespace n ON n.oid = e.extnamespace
             ORDER BY (ae.installed_version IS NOT NULL) DESC, ae.name ASC'
        );

        return $stmt->fetchAll();
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
     * Builds a WHERE clause (with bound params) from an array of filter conditions.
     * All conditions are combined with AND.
     *
     * @param  array<int, array{col: string, val: string, op: string}>  $filters
     * @return array{string, array<int, mixed>}
     */
    private function buildSearchWhere(string $qualifiedName, array $filters): array
    {
        $conditions = [];
        $params = [];

        foreach ($filters as $filter) {
            $col = $filter['col'] ?? null;
            $val = $filter['val'] ?? null;
            $op = $filter['op'] ?? 'contains';

            if (! $col || $val === null || $val === '') {
                continue;
            }

            try {
                $validatedCol = $this->validateColumnName($qualifiedName, $col);
            } catch (RuntimeException) {
                continue; // skip unknown columns rather than erroring
            }

            $type = $this->getColumnType($qualifiedName, $validatedCol);

            [$condition, $condParams] = $this->buildSingleCondition($validatedCol, $val, $op, $type);
            $conditions[] = $condition;
            $params = [...$params, ...$condParams];
        }

        if (empty($conditions)) {
            return ['', []];
        }

        return [' WHERE '.implode(' AND ', $conditions), $params];
    }

    /**
     * Builds a single SQL predicate (no WHERE keyword) for one filter condition.
     *
     * @return array{string, array<int, mixed>}
     */
    private function buildSingleCondition(string $col, string $val, string $op, string $type): array
    {
        $numericTypes = ['integer', 'bigint', 'smallint', 'serial', 'bigserial',
            'numeric', 'decimal', 'real', 'double precision', 'money'];

        return match (true) {
            $type === 'jsonb' && $op === 'jsonb_contains' => [
                "\"{$col}\" @> ?::jsonb", [$val],
            ],
            $type === 'jsonb' => [
                "\"{$col}\"::text ILIKE ?", ['%'.$val.'%'],
            ],
            $type === 'boolean' => [
                "\"{$col}\" = ?::boolean",
                [in_array(strtolower($val), ['true', '1', 'yes', 't', 'on']) ? 'true' : 'false'],
            ],
            in_array($type, $numericTypes) => [
                "\"{$col}\"::text LIKE ?", [$val.'%'],
            ],
            $op === 'starts_with' => [
                "\"{$col}\" ILIKE ?", [$val.'%'],
            ],
            $op === 'equals' => [
                "\"{$col}\" = ?", [$val],
            ],
            default => [
                "\"{$col}\" ILIKE ?", ['%'.$val.'%'],
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
