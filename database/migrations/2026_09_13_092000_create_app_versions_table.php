<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('platform')->default('android'); // android, ios
            $table->string('latest_version')->default('1.0.0');
            $table->string('min_version')->default('1.0.0');
            $table->boolean('force_update')->default(false);
            $table->string('title')->default('Update Available');
            $table->text('release_notes')->nullable();
            $table->string('apk_url')->nullable();
            $table->string('store_url')->nullable();
            $table->timestamps();

            $table->unique('platform');
        });

        // Seed initial default version configuration
        DB::table('app_versions')->insert([
            'platform' => 'android',
            'latest_version' => '1.0.0',
            'min_version' => '1.0.0',
            'force_update' => false,
            'title' => 'Update Available',
            'release_notes' => 'General stability improvements and performance enhancements.',
            'apk_url' => 'https://sa.devtz.com/downloads/SmartAttend.apk',
            'store_url' => 'https://sa.devtz.com/downloads/SmartAttend.apk',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};

