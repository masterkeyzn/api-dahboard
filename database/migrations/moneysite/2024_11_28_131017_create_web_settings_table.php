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
        Schema::create('web_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name', 100)->nullable();
            $table->string('site_title', 255)->nullable();
            $table->text('marquee')->nullable();
            $table->string('site_logo', 255)->nullable();
            $table->text('popup')->nullable();
            $table->text('sc_livechat')->nullable();
            $table->string('url_livechat', 255)->nullable();
            $table->string('proggressive_img', 255)->nullable();
            $table->string('themes', 50)->nullable();
            $table->string('favicon', 255)->nullable();
            $table->unsignedInteger('min_deposit')->nullable();
            $table->unsignedInteger('max_deposit')->nullable();
            $table->unsignedInteger('min_withdrawal')->nullable();
            $table->unsignedInteger('max_withdrawal')->nullable();
            $table->string('unique_code', 50)->nullable();
            $table->boolean('is_maintenance')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_settings');
    }
};
