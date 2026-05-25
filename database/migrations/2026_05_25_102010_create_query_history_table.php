<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'internal';

    public function up(): void
    {
        Schema::connection('internal')->create('query_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('connection_id');
            $table->text('sql');
            $table->unsignedInteger('duration_ms')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('executed_at');
        });
    }

    public function down(): void
    {
        Schema::connection('internal')->dropIfExists('query_history');
    }
};
