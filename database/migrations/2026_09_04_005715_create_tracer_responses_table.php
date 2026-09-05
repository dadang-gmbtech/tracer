<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Column groups mirror the official Kemdiktisaintek tracer study field codes
     * (see Form Tracer Studi.pdf / Contoh data.csv). Free-text or historically
     * "dirty" fields (mixed numeric/text answers in real submissions) are kept as
     * nullable strings; fields that drive IKU/dashboard math are sanitized to a
     * clean numeric type at write time (import/seed/form layer), not here.
     */
    public function up(): void
    {
        Schema::create('tracer_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumni_id')->unique()->constrained('alumni')->cascadeOnDelete();

            // Status & employment basics
            $table->unsignedTinyInteger('f8')->nullable(); // status saat ini
            $table->string('f504')->nullable();
            $table->unsignedSmallInteger('f502')->nullable(); // masa tunggu (bulan)
            $table->decimal('f505', 15, 2)->nullable(); // gaji per bulan
            $table->string('f506')->nullable();

            // Work location
            $table->string('f5a1')->nullable(); // kode provinsi kerja (raw)
            $table->string('f5a2')->nullable(); // kode kab/kota kerja (raw)
            $table->foreignId('work_province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->foreignId('work_city_id')->nullable()->constrained('cities')->nullOnDelete();

            // Employer / wirausaha details
            $table->unsignedTinyInteger('f1101')->nullable();
            $table->string('f1102')->nullable();
            $table->string('f5b')->nullable(); // nama perusahaan
            $table->unsignedTinyInteger('f5c')->nullable(); // posisi wirausaha
            $table->unsignedTinyInteger('f5d')->nullable(); // tingkat tempat kerja

            // Studi lanjut
            $table->unsignedTinyInteger('f18a')->nullable();
            $table->string('f18b')->nullable();
            $table->string('f18c')->nullable();
            $table->string('f18d')->nullable();

            // Pembiayaan kuliah
            $table->unsignedTinyInteger('f1201')->nullable();
            $table->string('f1202')->nullable();

            // Relevansi pekerjaan
            $table->unsignedTinyInteger('f14')->nullable();
            $table->unsignedTinyInteger('f15')->nullable();

            // Kompetensi: A (saat lulus) / B (dibutuhkan pekerjaan)
            foreach (range(1761, 1774) as $code) {
                $table->unsignedTinyInteger("f{$code}")->nullable();
            }

            // Metode pembelajaran
            foreach (range(21, 27) as $code) {
                $table->unsignedTinyInteger("f{$code}")->nullable();
            }

            // Pencarian kerja
            $table->unsignedTinyInteger('f301')->nullable();
            $table->unsignedSmallInteger('f302')->nullable();
            $table->unsignedSmallInteger('f303')->nullable();
            foreach (range(401, 415) as $code) {
                $table->boolean("f{$code}")->nullable();
            }
            $table->string('f416')->nullable();

            $table->unsignedSmallInteger('f6')->nullable();
            $table->unsignedSmallInteger('f7')->nullable();
            $table->unsignedSmallInteger('f7a')->nullable();

            $table->unsignedTinyInteger('f1001')->nullable();
            $table->string('f1002')->nullable();

            // Ketidaksesuaian pekerjaan dengan pendidikan
            foreach (range(1601, 1613) as $code) {
                $table->boolean("f{$code}")->nullable();
            }
            $table->string('f1614')->nullable();

            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracer_responses');
    }
};
