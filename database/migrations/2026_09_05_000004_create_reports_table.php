<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('level'); // universitas | fakultas | prodi
            $table->foreignId('faculty_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('program_study_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path');
            $table->year('year');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
