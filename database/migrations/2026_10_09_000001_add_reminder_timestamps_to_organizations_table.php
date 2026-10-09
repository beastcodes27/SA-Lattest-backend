<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->timestamp('renewal_reminder_sent_at')->nullable()->after('canceled_at');
            $table->timestamp('offer_reminder_sent_at')->nullable()->after('renewal_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['renewal_reminder_sent_at', 'offer_reminder_sent_at']);
        });
    }
};
