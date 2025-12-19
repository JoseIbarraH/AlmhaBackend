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
        Schema::create('procedure_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(0);

            $table->index('procedure_id');
        });

        Schema::create('procedure_faq_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_faq_id')->constrained()->onDelete('cascade');
            $table->string('lang', 5);
            $table->text('question');
            $table->text('answer');

            $table->unique(['procedure_faq_id', 'lang']);
            $table->index('lang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedure_faqs');
        Schema::dropIfExists('procedure_faq_translations');
    }
};
