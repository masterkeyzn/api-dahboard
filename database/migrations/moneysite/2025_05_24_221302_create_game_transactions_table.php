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
        Schema::create('game_transactions', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('status');
            $table->string('msg')->nullable();
            $table->string('agent_code', 50);
            $table->bigInteger('agent_balance');
            $table->string('agent_type', 50);
            $table->string('user_code', 100);
            $table->bigInteger('user_balance');
            $table->bigInteger('deposit_amount');
            $table->string('currency', 10)->default('IDR');
            $table->bigInteger('order_no');

            $table->unsignedBigInteger('admin_id')->nullable();
            $table->enum('action_by', ['system', 'admin', 'user'])->default('system');
            $table->string('action_note')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_transactions');
    }
};
