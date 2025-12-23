<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('actor_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('level');
            $table->text('message');
            $table->string('exception_class')->nullable();
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->text('trace')->nullable();
            $table->jsonb('context')->nullable();
            $table->string('route')->nullable();
            $table->string('method')->nullable();
            $table->text('url')->nullable();
            $table->string('ip')->nullable();
            $table->string('request_id')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
