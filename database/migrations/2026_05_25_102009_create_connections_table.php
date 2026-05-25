<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'internal';

    public function up(): void
    {
        Schema::connection('internal')->create('connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('host');
            $table->unsignedSmallInteger('port')->default(5432);
            $table->string('database');
            $table->string('username');
            $table->text('password');
            $table->boolean('ssl')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('internal')->dropIfExists('connections');
    }
};
