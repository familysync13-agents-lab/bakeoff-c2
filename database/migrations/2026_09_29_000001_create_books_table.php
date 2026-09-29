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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reading_list_id')->constrained()->cascadeOnDelete();
            // Open Library work key ("/works/OL…W"), or a hash of title/authors/year when the API omits the key.
            $table->string('identity', 255);
            $table->string('title', 500);
            $table->text('authors')->default('');
            $table->integer('first_publish_year')->nullable();
            $table->timestamps();

            $table->unique(['reading_list_id', 'identity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
