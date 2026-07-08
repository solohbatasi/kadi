<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_link_id')->nullable()->constrained()->nullOnDelete();
            $table->string('public_id')->unique();
            $table->string('invoice_number');
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('currency')->default('KES');
            $table->integer('subtotal')->default(0);
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->integer('tax_amount')->default(0);
            $table->integer('discount_amount')->default(0);
            $table->integer('total')->default(0);
            $table->enum('status', ['draft', 'open', 'paid', 'void'])->default('draft');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['merchant_id', 'invoice_number']);
            $table->index(['merchant_id', 'status']);
            $table->index(['merchant_id', 'created_at']);
            $table->index(['payment_link_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
