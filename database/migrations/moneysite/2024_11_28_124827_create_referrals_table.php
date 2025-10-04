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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->unique();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('referral_code')->nullable()->unique();
            $table->longText('id_card');

            $table->enum('status', ['verify', 'active', 'suspended'])->default('verify');
            $table->decimal('referral_balance', 16, 2)->default(0);

            $table->enum('commission_ndp_type', ['percent', 'idr'])->default('idr');
            $table->bigInteger('commission_ndp_value')->default(0);

            $table->enum('commission_rdp_type', ['percent', 'idr'])->default('idr');
            $table->bigInteger('commission_rdp_value')->default(0);

            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
