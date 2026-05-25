<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedQuery extends Model
{
    protected $connection = 'internal';

    protected $fillable = ['user_id', 'connection_id', 'name', 'sql'];
}
