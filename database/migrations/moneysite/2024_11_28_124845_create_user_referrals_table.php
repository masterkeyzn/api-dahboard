<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_referrals', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('referral_id');
            $table->unsignedBigInteger('user_id');

            $table->timestamp('first_deposit_at')->nullable();
            $table->decimal('first_deposit_amount', 16, 2)->nullable();
            $table->decimal('ndp_commission', 16, 2)->nullable();
            $table->unsignedInteger('total_deposit_count')->default(0);
            $table->decimal('rdp_commission_total', 16, 2)->default(0);

            $table->decimal('commission_earned', 16, 2)->default(0);

            $table->foreign('referral_id')->references('id')->on('referrals')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_referrals');
    }
};
