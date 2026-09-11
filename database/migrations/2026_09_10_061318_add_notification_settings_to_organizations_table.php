<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('shift_start', 5)->default('09:00')->after('discount_percent');
            $table->unsignedInteger('reminder_minutes')->default(30)->after('shift_start');
            $table->boolean('digest_enabled')->default(true)->after('reminder_minutes');
            $table->string('digest_time', 5)->default('18:00')->after('digest_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['shift_start', 'reminder_minutes', 'digest_enabled', 'digest_time']);
        });
    }
};
