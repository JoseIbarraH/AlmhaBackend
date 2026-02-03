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
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('name', 255)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->string('image')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('name');
            $table->index('status');
        });

        Schema::create('team_member_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_member_id')->constrained('team_members')->onDelete('cascade');
            $table->string('lang', 5);
            $table->string('specialization')->nullable();
            $table->text('description')->nullable();
            $table->text('biography')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_member_translations');
        Schema::dropIfExists('team_members');
    }
};
