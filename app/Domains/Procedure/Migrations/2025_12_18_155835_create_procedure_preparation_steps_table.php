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
        Schema::create('procedure_preparation_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(0);

            $table->index('procedure_id');
        });

        Schema::create('procedure_preparation_step_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_preparation_id')
                ->constrained('procedure_preparation_steps', 'id', 'prep_step_trans_prep_id_foreign')
                ->onDelete('cascade');
            $table->string('lang', 5);
            $table->string('title');
            $table->text('description');

            $table->unique(['procedure_preparation_id', 'lang'], 'prep_step_lang_unique');
            $table->index('lang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedure_preparation_steps');
        Schema::dropIfExists('procedure_preparation_step_translations');
    }
};
