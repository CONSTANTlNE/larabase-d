<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'internal';

    public function up(): void
    {
        Schema::connection('internal')->create('saved_queries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('connection_id');
            $table->string('name');
            $table->text('sql');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('internal')->dropIfExists('saved_queries');
    }
};
