<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueryHistory extends Model
{
    protected $connection = 'internal';

    protected $table = 'query_history';

    public $timestamps = false;

    protected $fillable = ['user_id', 'connection_id', 'sql', 'duration_ms', 'error', 'executed_at'];

    protected function casts(): array
    {
        return [
            'executed_at' => 'datetime',
            'duration_ms' => 'integer',
        ];
    }
}
