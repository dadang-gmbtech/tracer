<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The updated national tracer form (Panduan Form - Tracer Study.pdf) adds:
     * - F1775-F1782: 4 more kompetensi A/B pairs (Berpikir kritis, Kreativitas,
     *   Kewirausahaan, Adaptasi) on top of the existing 7.
     * - F28-F37: 10 more metode-pembelajaran items (Responsi/tutorial, Seminar,
     *   Studio, Perancangan, Pengembangan, Tugas akhir, Pelatihan bela negara,
     *   Pertukaran pelajar, Wirausaha, Pengabdian masyarakat) on top of F21-F27.
     * - F5E: whether a wirausaha/wiraswasta is berizin/terdaftar (0/1), not
     *   previously collected at all.
     */
    public function up(): void
    {
        Schema::table('tracer_responses', function (Blueprint $table) {
            foreach (range(1775, 1782) as $code) {
                $table->unsignedTinyInteger("f{$code}")->nullable()->after('f1774');
            }

            foreach (range(28, 37) as $code) {
                $table->unsignedTinyInteger("f{$code}")->nullable()->after('f27');
            }

            $table->unsignedTinyInteger('f5e')->nullable()->after('f5d');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracer_responses', function (Blueprint $table) {
            $table->dropColumn([
                ...array_map(fn ($code) => "f{$code}", range(1775, 1782)),
                ...array_map(fn ($code) => "f{$code}", range(28, 37)),
                'f5e',
            ]);
        });
    }
};
