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
        Schema::create('home_page_contents', function (Blueprint $table) {
            $table->id();
            $table->string('hero_title');
            $table->string('hero_subtitle');
            $table->text('hero_description');
            $table->text('tentang_description');
            // Each: {"title": string, "description": string} — always exactly 3, matching
            // the 3 fixed icons in home.blade.php.
            $table->json('tentang_cards');
            // 4 plain strings — always exactly 4, matching the 4 fixed step numbers.
            $table->json('alur_steps');
            $table->string('footer_address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_page_contents');
    }
};
