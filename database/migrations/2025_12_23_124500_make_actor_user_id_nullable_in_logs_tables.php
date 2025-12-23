<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['audit_logs', 'error_logs'] as $table) {
            $column = DB::selectOne(
                'select is_nullable from information_schema.columns where table_schema = current_schema() and table_name = ? and column_name = ? limit 1',
                [$table, 'actor_user_id']
            );

            if (($column?->is_nullable ?? 'YES') === 'NO') {
                DB::statement("alter table {$table} alter column actor_user_id drop not null");
            }
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['audit_logs', 'error_logs'] as $table) {
            $nullCount = DB::table($table)->whereNull('actor_user_id')->count();

            if ($nullCount === 0) {
                DB::statement("alter table {$table} alter column actor_user_id set not null");
            }
        }
    }
};
