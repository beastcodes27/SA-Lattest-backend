<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('face_enrolled')->default(false)->after('avatar_path');
            $table->longText('face_signature')->nullable()->after('face_enrolled');
            $table->string('face_photo_path')->nullable()->after('face_signature');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['face_enrolled', 'face_signature', 'face_photo_path']);
        });
    }
};
