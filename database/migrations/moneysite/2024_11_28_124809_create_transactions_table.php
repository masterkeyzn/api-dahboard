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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->enum('type', [
                'Deposit',
                'Withdrawal',
                'Bonus',
                'Cashback',
                'Manual_Deposit',
                'Manual_Withdrawal'
            ]);

            $table->string('transaction_id')->unique();

            $table->decimal('amount', 16, 2);

            $table->string('recipient_bank_name')->nullable();
            $table->string('recipient_account_number')->nullable();
            $table->string('recipient_account_name')->nullable();

            $table->string('sender_bank_name')->nullable();
            $table->string('sender_account_number')->nullable();
            $table->string('sender_account_name')->nullable();

            $table->unsignedBigInteger('bonus_id')->nullable();

            $table->string('note')->nullable();

            $table->string('admin')->nullable();

            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreign('bonus_id')->references('id')->on('bonusdeposits')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
