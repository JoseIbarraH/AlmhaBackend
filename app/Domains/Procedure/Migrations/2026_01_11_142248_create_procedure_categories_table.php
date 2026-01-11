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
        Schema::create('procedure_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
        });

        Schema::create('procedure_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('procedure_categories')->onDelete('cascade');
            $table->string('lang', 5);
            $table->string('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedure_categories');
        Schema::dropIfExists('procedure_category_translations');
    }
};
