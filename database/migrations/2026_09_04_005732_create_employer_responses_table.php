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
        Schema::create('employer_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumni_id')->constrained('alumni')->onDelete('cascade');
            $table->string('nama_pengisi');
            $table->string('jabatan')->nullable();
            $table->string('nama_perusahaan');
            $table->text('alamat_perusahaan')->nullable();
            $table->string('no_telp')->nullable();
            // Kuesioner Pengguna Alumni: 1=Sangat Baik, 2=Baik, 3=Cukup, 4=Kurang
            $table->unsignedTinyInteger('q1_kerja_sama_tim');
            $table->unsignedTinyInteger('q2_pengembangan_diri');
            $table->unsignedTinyInteger('q3_komunikasi');
            $table->unsignedTinyInteger('q4_teknologi_informasi');
            $table->unsignedTinyInteger('q5_bahasa_asing');
            $table->unsignedTinyInteger('q6_keahlian');
            $table->unsignedTinyInteger('q7_integritas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employer_responses');
    }
};
