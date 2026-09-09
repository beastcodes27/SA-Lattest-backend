<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_id')->constrained('promos')->cascadeOnDelete();
            $table->foreignId('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['promo_id', 'org_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_redemptions');
    }
};
