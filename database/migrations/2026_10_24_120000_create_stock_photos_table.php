<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_photos', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20);            // pexels | unsplash
            $table->string('source_id', 64);
            $table->string('url', 500);              // base image URL (sized per placement at render time)
            $table->string('thumb_url', 500);
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->string('orientation', 10);       // landscape | portrait | square
            $table->string('category', 30)->index(); // picker category (food, business, …)
            $table->string('topic', 80);             // the search topic it was imported for
            $table->string('alt', 300)->nullable();  // the source's own alt text
            $table->string('avg_color', 7)->nullable();
            $table->string('photographer', 120)->nullable();
            $table->string('photographer_url', 300)->nullable();
            // Filled by photos:analyze (vision model)
            $table->string('ai_description', 400)->nullable();
            $table->json('ai_tags')->nullable();      // ~10 normalised lowercase tags
            $table->json('ai_subjects')->nullable();  // 1–3 main subjects, most important first
            $table->boolean('people')->nullable();
            $table->string('person', 10)->nullable(); // man | woman | null (for portraits)
            $table->string('mood', 40)->nullable();
            $table->json('placements')->nullable();   // subset of hero|card|gallery|portrait|background
            $table->text('search_text')->nullable();  // denormalised, lowercase: tags + subjects + description + alt
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'source_id']);
            $table->index(['analyzed_at']);
            $table->index(['orientation', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_photos');
    }
};
