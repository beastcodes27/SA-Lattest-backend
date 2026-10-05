<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway')->default('sonicpesa')->after('currency');
            $table->string('sonicpesa_order_id')->nullable()->index()->after('reference');
            $table->string('sonicpesa_checkout_url')->nullable()->after('sonicpesa_order_id');
            $table->text('sonicpesa_qr_code')->nullable()->after('sonicpesa_checkout_url');
            $table->json('sonicpesa_response')->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['sonicpesa_order_id']);
            $table->dropColumn([
                'gateway',
                'sonicpesa_order_id',
                'sonicpesa_checkout_url',
                'sonicpesa_qr_code',
                'sonicpesa_response',
            ]);
        });
    }
};
