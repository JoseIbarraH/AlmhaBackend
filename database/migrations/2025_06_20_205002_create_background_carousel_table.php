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
        Schema::create('background_carousels', function (Blueprint $table) {
            $table->id();
            $table->string('url'); // Ruta al archivo de imagen
            $table->string('title')->nullable(); // Título opcional
            $table->text('text')->nullable(); // Descripción opcional
            $table->unsignedInteger('order')->default(0); // Orden en el carrusel
            $table->string('type')->default('carousel'); // Tipo: carousel, fondo, etc.
            $table->string('typefile')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('background_carousel');
    }
};
