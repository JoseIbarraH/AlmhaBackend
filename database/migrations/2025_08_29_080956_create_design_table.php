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
        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->json('value');
            $table->timestamps();
        });

        DB::table('designs')->insert([
            ['key' => 'carousel', 'value' => json_encode(true), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'imageVideo', 'value' => json_encode(false), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'carouselUrls', 'value' => json_encode([['url' => 'images/general/start/1423.jpg', 'text' => 'default', 'title' => 'default']]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'imageVideoUrl', 'value' => json_encode('images/general/start/1423.jpg'), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'background1', 'value' => json_encode('images/general/start/1423.jpg'), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'background2', 'value' => json_encode('images/general/start/1423.jpg'), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'background3', 'value' => json_encode('images/general/start/1423.jpg'), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'carouselNavbar', 'value' => json_encode(['images/general/start/1423.jpg']), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designs');
    }
};
