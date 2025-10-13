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
            $table->timestamps();
        });

        DB::table('design_settings')->insert([
            ['key' => 'imageVideo', 'value' => false, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'carousel', 'value' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'carouselNavbar', 'value' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'carouselTool', 'value' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'background1', 'value' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'background2', 'value' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'background3', 'value' => true, 'created_at' => now(), 'updated_at' => now()],

        ]);

        Schema::create('design_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')->constrained('design_settings')->onDelete('cascade');
            $table->string('lang', 5)->default('es');
            $table->string('type');
            $table->string('path');
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designs');
    }
};
