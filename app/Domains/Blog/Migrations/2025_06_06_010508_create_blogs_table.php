<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        /**
         * 1. BLOG CATEGORIES
         */
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
        });

        // Datos iniciales para permitir SET DEFAULT = 1
        // Datos iniciales movidos a BlogCategorySeeder

        /**
         * 2. BLOG CATEGORY TRANSLATIONS
         */
        Schema::create('blog_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('blog_categories')->onDelete('cascade');
            $table->string('lang', 5);
            $table->string('title');
        });

        // Datos iniciales movidos a BlogCategorySeeder
        /**
         * 3. BLOGS
         */
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('image')->nullable();
            $table->string('category_code', 50)->nullable();
            $table->string('writer')->nullable();
            $table->integer('view')->default(0);
            $table->enum('status', ['inactive', 'active'])->default('inactive');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_code')->references('code')->on('blog_categories')->nullOnDelete();
        });

        /**
         * 4. BLOG TRANSLATIONS
         */
        Schema::create('blog_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_id')->constrained('blogs')->onDelete('cascade');
            $table->string('lang', 5);
            $table->string('title');
            $table->longText('content');
            $table->unique(['blog_id', 'lang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_category_translations');
        Schema::dropIfExists('blog_categories');
        Schema::dropIfExists('blog_translations');
        Schema::dropIfExists('blogs');
    }
};
