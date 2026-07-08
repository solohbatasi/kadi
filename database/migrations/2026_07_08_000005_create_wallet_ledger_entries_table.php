<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('public_id')->unique();
            $table->enum('entry_type', ['payment_credit', 'commission_debit', 'payout_debit', 'payout_reversal', 'topup_credit', 'manual_adjustment']);
            $table->enum('direction', ['credit', 'debit']);
            $table->integer('amount');
            $table->integer('balance_after');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['merchant_id']);
            $table->index(['wallet_id']);
            $table->index(['transaction_id']);
            $table->index(['entry_type']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_ledger_entries');
    }
};
