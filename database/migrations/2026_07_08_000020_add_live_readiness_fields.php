<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->timestamp('live_requested_at')->nullable()->after('live_enabled');
            $table->timestamp('live_reviewed_at')->nullable()->after('live_requested_at');
            $table->text('live_rejection_reason')->nullable()->after('live_reviewed_at');
        });

        Schema::table('merchant_profiles', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('address');
            $table->timestamp('privacy_accepted_at')->nullable()->after('terms_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_profiles', function (Blueprint $table) {
            $table->dropColumn(['terms_accepted_at', 'privacy_accepted_at']);
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['live_requested_at', 'live_reviewed_at', 'live_rejection_reason']);
        });
    }
};

