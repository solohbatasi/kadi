<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payout_recipient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('public_id')->unique();
            $table->integer('amount');
            $table->string('currency')->default('KES');
            $table->integer('fee')->default(0);
            $table->integer('net_amount');
            $table->string('phone');
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'reversed', 'cancelled'])->default('pending');
            $table->string('provider')->nullable();
            $table->string('provider_conversation_id')->nullable()->index();
            $table->string('provider_originator_conversation_id')->nullable()->index();
            $table->string('provider_result_code')->nullable();
            $table->text('provider_result_description')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
            $table->index(['merchant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
