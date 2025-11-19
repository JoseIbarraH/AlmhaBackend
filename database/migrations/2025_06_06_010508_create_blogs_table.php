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
            $table->string('code')->unique();
        });

        // Datos iniciales para permitir SET DEFAULT = 1
        DB::table('blog_categories')->insert([
            ['id' => 1, 'code' => 'general'],
            ['id' => 2, 'code' => 'facial'],
            ['id' => 3, 'code' => 'bodily'],
            ['id' => 4, 'code' => 'non-surgical'],
        ]);

        /**
         * 2. BLOG CATEGORY TRANSLATIONS
         */
        Schema::create('blog_category_translations', function (Blueprint $table){
            $table->id();
            $table->foreignId('category_id')->constrained('blog_categories')->onDelete('cascade');
            $table->string('lang', 5);
            $table->string('title');
        });

        DB::table('blog_category_translations')->insert([
            ['id' => 1, 'category_id' => 1, 'lang' => 'es', 'title' => 'General'],
            ['id' => 2, 'category_id' => 1, 'lang' => 'en', 'title' => 'General'],
            ['id' => 3, 'category_id' => 2, 'lang' => 'es', 'title' => 'Facial'],
            ['id' => 4, 'category_id' => 2, 'lang' => 'en', 'title' => 'Facial'],
            ['id' => 5, 'category_id' => 3, 'lang' => 'es', 'title' => 'Corporal'],
            ['id' => 6, 'category_id' => 3, 'lang' => 'en', 'title' => 'Bodily'],
            ['id' => 7, 'category_id' => 4, 'lang' => 'es', 'title' => 'No quirúrgico'],
            ['id' => 8, 'category_id' => 4, 'lang' => 'en', 'title' => 'Non-surgical'],
        ]);
        /**
         * 3. BLOGS
         */
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('image')->nullable();
            $table->unsignedBigInteger('category_id')->default(1);
            $table->string('writer')->nullable();
            $table->integer('view')->default(0);
            $table->enum('status', ['inactive', 'active'])->default('inactive');
            $table->timestamps();
            $table->foreign('category_id')->references('id')->on('blog_categories')->onDelete('set default');
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
        Schema::dropIfExists('blog_translations');
        Schema::dropIfExists('blogs');
        Schema::dropIfExists('blog_category_translations');
        Schema::dropIfExists('blog_categories');
    }
};
