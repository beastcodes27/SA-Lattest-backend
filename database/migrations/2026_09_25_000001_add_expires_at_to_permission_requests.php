<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permission_requests', function (Blueprint $table) {
            // Timestamp when the request automatically expires if not actioned
            $table->timestamp('expires_at')->nullable()->after('actioned_at');
            // Track when a reminder was last sent to org admin
            $table->timestamp('reminder_sent_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('permission_requests', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'reminder_sent_at']);
        });
    }
};
