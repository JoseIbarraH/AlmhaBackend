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
        Schema::create('design_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->boolean('value')->default(false);
        });

        DB::table('design_settings')->insert([
            ['key' => 'imageVideo', 'value' => false],
            ['key' => 'carousel', 'value' => true],
            ['key' => 'carouselNavbar', 'value' => true],
            ['key' => 'carouselTool', 'value' => true],
            ['key' => 'background1', 'value' => true],
            ['key' => 'background2', 'value' => true],
            ['key' => 'background3', 'value' => true],
        ]);

        Schema::create('design_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')->constrained('design_settings')->onDelete('cascade');
            $table->string('type');
            $table->string('path');
        });

        Schema::create('design_item_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('design_items')->onDelete('cascade');
            $table->string('lang', 5)->default('es');
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designs');
        Schema::dropIfExists('design_items');
        Schema::dropIfExists('design_item_translations');
    }
};
