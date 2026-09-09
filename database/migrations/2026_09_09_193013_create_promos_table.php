<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type');
            $table->unsignedInteger('value');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};
