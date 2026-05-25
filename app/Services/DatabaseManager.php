<?php

namespace App\Services;

use App\Models\Connection;
use App\Services\Drivers\DatabaseDriverInterface;
use App\Services\Drivers\PostgresDriver;
use InvalidArgumentException;

class DatabaseManager
{
    /** @var array<int, DatabaseDriverInterface> */
    private array $drivers = [];

    /**
     * Returns a cached driver instance for the connection within the current request.
     * A new PDO connection is still opened per request, but multiple calls with the
     * same connection ID reuse the same driver (and thus the same PDO handle).
     */
    public function driver(Connection $connection): DatabaseDriverInterface
    {
        if (! isset($this->drivers[$connection->id])) {
            $this->drivers[$connection->id] = match ($connection->driver ?? 'pgsql') {
                'pgsql' => new PostgresDriver($connection),
                default => throw new InvalidArgumentException("Unsupported driver: {$connection->driver}"),
            };
        }

        return $this->drivers[$connection->id];
    }
}
