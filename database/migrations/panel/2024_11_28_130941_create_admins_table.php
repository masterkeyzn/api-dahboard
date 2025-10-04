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
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_credential_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('max_transaction');
            $table->timestamps();

            $table->foreign('admin_credential_id')
                ->references('id')
                ->on('admin_credentials')
                ->onDelete('set null');

            $table->foreign('created_by')
                ->references('id')
                ->on('admins')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
