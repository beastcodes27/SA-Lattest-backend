<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
            $table->string('paused_plan')->nullable()->after('discount_percent');
            $table->unsignedBigInteger('paused_remaining_seconds')->default(0)->after('paused_plan');
            $table->boolean('paused_was_trial')->default(false)->after('paused_remaining_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_ends_at',
                'paused_plan',
                'paused_remaining_seconds',
                'paused_was_trial',
            ]);
        });
    }
};
