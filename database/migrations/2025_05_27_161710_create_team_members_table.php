<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('team_member_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_member_id')->constrained('team_members')->onDelete('cascade');
            $table->string('lang', 5);
            $table->string('specialization');
            $table->text('biography');
            $table->timestamps();
        });

        Schema::create('team_member_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_member_id')->constrained('team_members')->onDelete('cascade');
            $table->string('lang', 5);
            $table->string('url', 400);
            $table->string('description', 400)->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('team_member_translations');
        Schema::dropIfExists('team_member_medias');
    }
};
