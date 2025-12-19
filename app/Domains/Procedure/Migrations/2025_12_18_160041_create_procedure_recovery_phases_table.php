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
        Schema::create('procedure_recovery_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('procedure_id');
        });

        Schema::create('procedure_recovery_phase_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('procedure_recovery_phase_id')
            ->constrained('procedure_recovery_phases', 'id', 'pro_rec_phase_id_foreign')
            ->onDelete('cascade'); 

            $table->string('lang', 5);
            $table->string('period');
            $table->string('title');
            $table->text('description');

            $table->unique(
                ['procedure_recovery_phase_id', 'lang'],
                'rec_phase_lang_unique'
            );

            $table->index('lang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedure_recovery_phases');
        Schema::dropIfExists('procedure_recovery_phase_translations');
    }
};
