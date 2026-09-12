<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('check_in_time', 10)->default('09:00')->after('radius_meters');
            $table->unsignedInteger('grace_period_minutes')->default(15)->after('check_in_time');
            $table->string('check_out_time', 10)->default('17:00')->after('grace_period_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['check_in_time', 'grace_period_minutes', 'check_out_time']);
        });
    }
};
