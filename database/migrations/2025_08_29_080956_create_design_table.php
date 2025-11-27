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
            $table->string('folder')->default('images/design/default');
        });

        DB::table('design_settings')->insert([
            ['key' => 'imageVideo', 'value' => false, 'folder' => 'images/design/imageVideo'],
            ['key' => 'carousel', 'value' => true, 'folder' => 'images/design/carousel'],
            ['key' => 'carouselNavbar', 'value' => true, 'folder' => 'images/design/carouselNavbar'],
            ['key' => 'carouselTool', 'value' => true, 'folder' => 'images/design/carouselTool'],
            ['key' => 'background1', 'value' => true, 'folder' => 'images/design/background/background1'],
            ['key' => 'background2', 'value' => true, 'folder' => 'images/design/background/background2'],
            ['key' => 'background3', 'value' => true, 'folder' => 'images/design/background/background3'],
        ]);

        Schema::create('design_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')->constrained('design_settings')->onDelete('cascade');
            $table->string('type');
            $table->string('path');
        });

        DB::table('design_items')->insert([
            ['design_id' => '1', 'type' => 'image', 'path' => 'images/design/default/default.webp'],
            ['design_id' => '5', 'type' => 'image', 'path' => 'images/design/default/default.webp'],
            ['design_id' => '6', 'type' => 'image', 'path' => 'images/design/default/default.webp'],
            ['design_id' => '7', 'type' => 'image', 'path' => 'images/design/default/default.webp'],
        ]);

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
