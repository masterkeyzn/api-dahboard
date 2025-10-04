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
        Schema::create('user_bonusdeposits', function (Blueprint $table) {
            $table->id();

            // Relasi ke users
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Relasi ke bonusdeposits
            $table->unsignedBigInteger('bonusdeposit_id');
            $table->foreign('bonusdeposit_id')->references('id')->on('bonusdeposits')->onDelete('cascade');

            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->unsignedInteger('claim_count')->default(1);
            $table->integer('deposit_amount')->nullable();
            $table->integer('bonus_amount')->nullable();
            $table->integer('achieved_turnover')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_bonus_deposits');
    }
};
