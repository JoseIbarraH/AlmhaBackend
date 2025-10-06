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
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            // Polymorphic relation (Laravel standard)
            $table->string('model_type', 50);
            $table->unsignedBigInteger('model_id');

            // Media attributes
            $table->string('type', 50)->default('gallery'); // e.g. 'main','example','gallery','avatar','banner'
            $table->enum('media_type', ['image', 'video'])->default('image');

            // File info
            $table->string('path', 255);
            $table->string('title', 255)->nullable();

            // Ordering & timestamps
            $table->integer('order')->default(0);
            $table->uuid('group_id')->nullable();
            $table->timestamps();


            // Indexes
            $table->index(['model_type', 'model_id']);
            $table->index('type');
            $table->index('media_type');
        });


        DB::table('media')->insert([

        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
