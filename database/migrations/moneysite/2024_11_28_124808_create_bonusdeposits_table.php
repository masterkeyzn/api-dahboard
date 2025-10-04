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
        Schema::create('bonusdeposits', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['bonus_persen', 'bonus_fixed']);
            $table->enum('category', ['all', 'new']);
            $table->enum('condition_type', ['target_turnover', 'max_withdrawal']);
            $table->integer('amount');
            $table->integer('max_bonus');
            $table->integer('max_claims');
            $table->integer('min_deposit')->nullable();
            $table->integer('target_turnover')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonusdeposits');
    }
};
