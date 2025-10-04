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
        Schema::create('bankdeposits', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number');
            $table->string('min_deposit')->nullable();
            $table->string('max_deposit')->nullable();
            $table->string('unique_code')->nullable();
            $table->string('qris_img')->nullable();
            $table->enum('show_form', ['showQris', 'showAccNo'])->default('showAccNo');
            $table->enum('status_bank', ['maintenance', 'active', 'offline'])->default('active');
            $table->enum('show_bank', ['inactive', 'active'])->default('inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bankdeposits');
    }
};
