<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $recreate = ! Schema::hasTable('sessions');

        if (! $recreate && ! Schema::hasColumn('sessions', 'user_id')) {
            $recreate = true;
        }

        if (! $recreate) {
            $type = Schema::getColumnType('sessions', 'user_id');

            $recreate = in_array($type, ['int8', 'int4', 'int2', 'bigint', 'integer', 'int', 'smallint'], true);
        }

        if (! $recreate) {
            return;
        }

        Schema::dropIfExists('sessions');

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }
};
