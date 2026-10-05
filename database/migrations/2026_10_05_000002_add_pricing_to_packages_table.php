<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedBigInteger('monthly_price')->default(0)->after('price_label');
            $table->unsignedBigInteger('annual_price')->default(0)->after('monthly_price');
            $table->string('currency')->default('TZS')->after('annual_price');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['monthly_price', 'annual_price', 'currency']);
        });
    }
};
