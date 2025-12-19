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
        Schema::create('procedure_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'what_is', 'surgical_technique', 'recovery_info'
            $table->string('image')->nullable();
            $table->integer('order')->default(0);

            $table->index(['procedure_id', 'type']);
        });

        Schema::create('procedure_section_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_section_id')->constrained()->onDelete('cascade');
            $table->string('lang', 5);
            $table->string('title')->nullable();
            $table->text('content')->nullable();

            $table->unique(['procedure_section_id', 'lang'], 'section_lang_unique');
            $table->index('lang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedure_sections');
        Schema::dropIfExists('procedure_section_translations');
    }
};
