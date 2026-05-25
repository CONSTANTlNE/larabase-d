<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Connection extends Model
{
    protected $connection = 'internal';

    protected $fillable = ['user_id', 'name', 'host', 'port', 'database', 'username', 'password', 'ssl'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'ssl' => 'boolean',
        ];
    }
}
