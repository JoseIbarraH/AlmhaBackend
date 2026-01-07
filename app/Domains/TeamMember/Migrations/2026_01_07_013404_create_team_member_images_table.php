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

        Schema::create('team_member_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_member_id')->constrained('team_members')->onDelete('cascade');
            $table->string('path');
            $table->integer('order')->default(0);

            $table->index('team_member_id');
        });

        Schema::create('team_member_image_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_member_image_id')->constrained('team_member_images')->onDelete('cascade');
            $table->string('lang', 5);
            $table->string('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_member_image_translations');
        Schema::dropIfExists('team_member_images');
    }
};
