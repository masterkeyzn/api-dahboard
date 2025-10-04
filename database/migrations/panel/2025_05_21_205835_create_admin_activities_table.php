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
        Schema::create('admin_activities', function (Blueprint $table) {
            $table->id();

            // Admin yang melakukan aksi
            $table->unsignedBigInteger('admin_id');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');

            // Tipe aksi (bebas, misal: 'approve_deposit', 'delete_admin', dll)
            $table->string('action_type');

            // Target objek yang dipengaruhi (opsional)
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_type')->nullable(); // contoh: 'user', 'bonus', 'transaction'

            // Keterangan tambahan
            $table->text('description')->nullable();

            // IP admin saat melakukan aksi
            $table->ipAddress('ip_address')->nullable();

            $table->timestamps();

            // Indexing untuk kecepatan query
            $table->index(['admin_id', 'action_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_activities');
    }
};
