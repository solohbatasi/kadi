<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox');
            $table->string('publishable_key')->unique();
            $table->string('secret_key_hash')->unique();
            $table->string('secret_key_prefix');
            $table->string('secret_key_last_four');
            $table->enum('status', ['active', 'revoked'])->default('active');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
