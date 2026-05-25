<?php

namespace App\Services\Drivers;

interface DatabaseDriverInterface
{
    /**
     * @return array<int, array{schema: string, name: string}>
     */
    public function getTables(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getColumns(string $table): array;

    /**
     * @param  array<int, array{col: string, val: string, op: string}>  $filters
     * @return array{rows: array<int, array<string, mixed>>, total: int}
     */
    public function getRows(string $table, int $page = 1, int $perPage = 50, ?string $sortCol = null, string $sortDir = 'ASC', array $filters = []): array;

    /**
     * @return array{columns: array<int, array<string, mixed>>, indexes: array<int, array<string, mixed>>, foreign_keys: array<int, array<string, mixed>>}
     */
    public function getTableStructure(string $table): array;

    /**
     * @return array{columns: array<string>, rows: array<int, array<string, mixed>>, duration_ms: int, affected: int, error: string|null}
     */
    public function executeQuery(string $sql): array;

    /**
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array;

    public function getTableCount(string $table): int;

    /**
     * @return string[]
     */
    public function getPrimaryKeyColumns(string $qualifiedName): array;

    /**
     * @param  array<string, mixed>  $pkValues
     */
    public function deleteRow(string $qualifiedName, array $pkValues): void;

    /**
     * @param  array<string, mixed>  $pkValues
     * @param  array<string, mixed>  $newValues
     */
    public function updateRow(string $qualifiedName, array $pkValues, array $newValues): void;

    /**
     * @param  array<int, array<string, mixed>>  $pkValueSets
     */
    public function deleteRows(string $qualifiedName, array $pkValueSets): int;

    public function truncateTable(string $qualifiedName): void;

    public function dropTable(string $qualifiedName): void;

    /**
     * @return array{
     *   outgoing: array<int, array{constraint_name: string, columns: string[], foreign_schema: string, foreign_table: string, foreign_columns: string[], delete_rule: string, update_rule: string}>,
     *   incoming: array<int, array{constraint_name: string, referencing_schema: string, referencing_table: string, referencing_columns: string[], columns: string[], delete_rule: string, update_rule: string}>
     * }
     */
    public function getRelations(string $qualifiedName): array;

    /**
     * Returns per-column enum allowed values for an enum-typed columns.
     *
     * @return array<string, list<string>> column_name => [val, ...]
     */
    public function getColumnEnums(string $qualifiedName): array;

    /**
     * Returns the parsed EXPLAIN (FORMAT JSON) plan tree.
     *
     * @return array<string, mixed>
     */
    public function explainQuery(string $sql): array;

    /**
     * Returns slow-query data from pg_stat_statements.
     *
     * @return array{available: bool, rows: list<array<string, mixed>>}
     */
    public function getPgStatStatements(int $limit = 50): array;

    /**
     * Returns dead-tuple / bloat data for all user tables.
     *
     * @return list<array{schemaname: string, table_name: string, n_live_tup: int, n_dead_tup: int, bloat_pct: float, total_size: string, last_vacuum: string|null, last_autovacuum: string|null, last_analyze: string|null, last_autoanalyze: string|null}>
     */
    public function getTableBloat(): array;

    /**
     * Returns all extensions — installed and available.
     *
     * @return list<array{name: string, default_version: string|null, installed_version: string|null, comment: string|null, is_installed: int, schema_name: string|null}>
     */
    public function getExtensions(): array;
}
