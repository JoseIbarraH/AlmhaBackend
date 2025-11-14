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
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id') ->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('image')->nullable();
            $table->enum('category', ['general', 'facial', 'bodily', 'non-surgical'])->default('general');
            $table->string('writer')->nullable();
            $table->integer('view')->default(0);
            $table->enum('status', ['inactive', 'active'])->default('inactive');
            $table->timestamps();
        });

        Schema::create('blog_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_id')->constrained('blogs')->onDelete('cascade');
            $table->string('lang', 5);
            $table->string('title');
            $table->longText('content');
            $table->timestamps();

            $table->unique(['blog_id', 'lang']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_translations');
        Schema::dropIfExists('blogs');
    }
};
