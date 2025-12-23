<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('from_wallet_id')->nullable()->constrained('wallets');
            $table->foreignUuid('to_wallet_id')->nullable()->constrained('wallets');
            $table->foreignUuid('reversal_of_id')->nullable()->constrained('transactions');
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            $table->string('type');
            $table->string('status')->default('POSTED');
            $table->bigInteger('amount_cents');
            $table->string('description')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
