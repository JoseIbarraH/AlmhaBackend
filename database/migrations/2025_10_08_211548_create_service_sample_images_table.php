<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_sample_images', function (Blueprint $table) {
    $table->id();
    $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
    $table->text('technique')->nullable();
    $table->text('recovery')->nullable();
    $table->text('postoperative_care')->nullable();
    $table->string('path')->nullable(); // ✅ ESTA FALTABA
    $table->timestamps();
});

    }

    public function down(): void
    {
        Schema::dropIfExists('service_sample_images');
    }
};