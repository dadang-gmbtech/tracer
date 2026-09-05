<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->morphs('answerable'); // Alumni or EmployerResponse
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['question_id', 'answerable_type', 'answerable_id'], 'question_answers_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_answers');
    }
};
