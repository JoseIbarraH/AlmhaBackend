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
        Schema::create('procedure_postoperative_instructions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['do', 'dont']); // Lo que SÍ / Lo que NO

            $table->index(['procedure_id', 'type']);
        });

        Schema::create('procedure_postoperative_instruction_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('procedure_postoperative_instruction_id')
                ->constrained('procedure_postoperative_instructions', 'id', 'pro_post_inst_id_foreign')
                ->onDelete('cascade');

            $table->string('lang', 5);
            $table->string('title');
            $table->text('description');
            $table->integer('order')->default(0);

            $table->unique(['procedure_postoperative_instruction_id', 'lang'], 'post_inst_lang_unique');
            $table->index('lang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedure_postoperative_instructions');
        Schema::dropIfExists('procedure_postoperative_instruction_translations');
    }
};
