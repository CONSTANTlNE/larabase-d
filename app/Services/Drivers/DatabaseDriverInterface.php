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
     * @return array{rows: array<int, array<string, mixed>>, total: int}
     */
    public function getRows(string $table, int $page = 1, int $perPage = 50, ?string $sortCol = null, string $sortDir = 'ASC'): array;

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
     * @param array<string, mixed> $pkValues
     */
    public function deleteRow(string $qualifiedName, array $pkValues): void;

    /**
     * @param array<string, mixed> $pkValues
     * @param array<string, mixed> $newValues
     */
    public function updateRow(string $qualifiedName, array $pkValues, array $newValues): void;
}
