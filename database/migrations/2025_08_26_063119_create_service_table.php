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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('image')->nullable();
            $table->enum('status', ['active', 'inactive']);
            $table->string('slug')->unique();
            $table->integer('view')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('service_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->string('lang', 5)->default('es');
            ;
            $table->text('description');
            $table->string('title');
        });

        Schema::create('service_surgery_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->string('lang', 5)->default('es');
            $table->json('recovery_time')->nullable();
            $table->json('postoperative_recommendations')->nullable();
            $table->json('preoperative_recommendations')->nullable();
        });

        Schema::create('service_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->string('lang', 5)->default('es');
            $table->string('question', 255);
            $table->text('answer');
        });

        Schema::create('service_sample_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->string('technique')->nullable();
            $table->string('recovery')->nullable();
            $table->string('postoperative_care')->nullable();
        });

        Schema::create('service_result_galleries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->string('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_faq');
        Schema::dropIfExists('service_translations');
        Schema::dropIfExists('service_surgery_phases');
        Schema::dropIfExists('service_sample_images');
        Schema::dropIfExists('service_result_galleries');
        Schema::dropIfExists('services');
    }
};
