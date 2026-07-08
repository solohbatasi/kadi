<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->string('url');
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->integer('status_code')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->integer('attempts')->default(0);
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id']);
            $table->index(['status']);
            $table->index(['event']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_webhook_deliveries');
    }
};
