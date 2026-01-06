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

        /* DB::table('design_settings')->insert([
            ['key' => 'imageVideo', 'value' => false, 'folder' => 'images/design/imageVideo'],
            ['key' => 'carousel', 'value' => true, 'folder' => 'images/design/carousel'],
            ['key' => 'carouselNavbar', 'value' => true, 'folder' => 'images/design/carouselNavbar'],
            ['key' => 'carouselTool', 'value' => true, 'folder' => 'images/design/carouselTool'],
            ['key' => 'background1', 'value' => true, 'folder' => 'images/design/background/background1'],
            ['key' => 'background2', 'value' => true, 'folder' => 'images/design/background/background2'],
            ['key' => 'background3', 'value' => true, 'folder' => 'images/design/background/background3'],
            ['key' => 'maintenance', 'value' => true, 'folder' => 'images/design/maintenance'],
        ]); */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designs');
    }
};
