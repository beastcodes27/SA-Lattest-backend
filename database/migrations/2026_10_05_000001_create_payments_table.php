<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('plan');
            $table->string('billing_cycle')->default('monthly'); // 'monthly', 'annual'
            $table->unsignedBigInteger('amount'); // In TZS
            $table->unsignedBigInteger('original_amount');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->string('currency')->default('TZS');
            $table->string('mobile_provider'); // 'mpesa', 'tigopesa', 'airtelmoney', 'halopesa'
            $table->string('phone_number');
            $table->string('reference')->unique();
            $table->string('external_transaction_id')->nullable()->index();
            $table->string('ussd_code')->nullable();
            $table->string('status')->default('pending'); // 'pending', 'completed', 'failed', 'canceled'
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
