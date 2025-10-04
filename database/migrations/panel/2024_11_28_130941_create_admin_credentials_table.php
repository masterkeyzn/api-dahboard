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
        Schema::create('admin_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('pusher_key');
            $table->string('pusher_app_id');
            $table->string('pusher_secret');
            $table->string('agent_token');
            $table->string('database_host');
            $table->string('database_port');
            $table->string('database_username');
            $table->string('database_password');
            $table->string('database_name');
            $table->string('agent_code');
            $table->string('redis_host');
            $table->string('redis_password')->default('kmzwayxx');
            $table->string('redis_port')->default('6379');
            $table->string('redis_prefix');
            $table->timestamp('expired')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_credentials');
    }
};
