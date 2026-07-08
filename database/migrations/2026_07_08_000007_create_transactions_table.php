<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('public_id')->unique();
            $table->enum('type', ['stk_push', 'payment_link', 'invoice', 'topup', 'payout']);
            $table->enum('direction', ['credit', 'debit']);
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox');
            $table->string('phone')->nullable();
            $table->integer('amount');
            $table->string('currency')->default('KES');
            $table->integer('commission_amount')->default(0);
            $table->integer('provider_fee')->default(0);
            $table->integer('net_amount')->default(0);
            $table->enum('status', ['pending', 'success', 'failed', 'cancelled', 'timeout'])->default('pending');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->string('mpesa_checkout_request_id')->nullable()->unique();
            $table->string('mpesa_merchant_request_id')->nullable();
            $table->string('mpesa_receipt_number')->nullable();
            $table->string('mpesa_result_code')->nullable();
            $table->text('mpesa_result_description')->nullable();
            $table->text('customer_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id']);
            $table->index(['status']);
            $table->index(['type']);
            $table->index(['environment']);
            $table->index(['created_at']);
            $table->index(['idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
