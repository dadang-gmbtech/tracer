<?php

namespace Tests\Unit;

use App\Services\ReportAnalysisService;
use PHPUnit\Framework\TestCase;

class ReportAnalysisServiceTest extends TestCase
{
    private function current(array $overrides = []): array
    {
        return array_merge([
            'jumlah_alumni' => 1000, 'responden' => 850, 'persentase_responden' => 85.0, 'iku_berdasar_lulusan' => 55.0,
        ], $overrides);
    }

    private function breakdown(array $overrides = []): array
    {
        return array_merge([
            'status_lulusan' => ['bekerja' => 600, 'wiraswasta' => 50, 'studi_lanjut' => 100, 'belum_bekerja' => 100],
            'masa_tunggu' => ['<=6 bulan' => 500, '7-12 bulan' => 80, '>12 bulan' => 20],
            'pendapatan' => ['<3 juta' => 100, '3-5 juta' => 200, '5-10 juta' => 250, '>10 juta' => 50],
            'rata_rata_pendapatan' => 6_000_000,
            'tempat_kerja' => [
                ['name' => 'Jawa Tengah', 'jumlah' => 400, 'is_luar_negeri' => false],
                ['name' => 'Luar Negeri', 'jumlah' => 5, 'is_luar_negeri' => true],
            ],
            'f14' => ['Sangat relevan' => 300, 'Relevan' => 200, 'Cukup relevan' => 100, 'Kurang relevan' => 50, 'Tidak relevan' => 50],
            'f15' => ['Setingkat lebih tinggi' => 50, 'Tingkat yang sama' => 500, 'Setingkat lebih rendah' => 30, 'Tidak perlu pendidikan tinggi' => 20],
            'jenis_instansi' => ['Perusahaan swasta' => 400, 'Instansi/lembaga pemerintah' => 100, 'BUMN/BUMD' => 50, 'Lainnya' => 50],
            'ruang_lingkup' => ['Lokal/Wilayah' => 200, 'Nasional' => 300, 'Multinasional/Internasional' => 100],
            'cara_mencari_kerja' => ['basis' => 700, 'items' => [
                'Internet/medsos/job portal' => ['jumlah' => 350, 'persen' => 50.0],
                'Info karir dari fakultas/universitas' => ['jumlah' => 20, 'persen' => 2.9],
                'Kantor kemahasiswaan/alumni' => ['jumlah' => 10, 'persen' => 1.4],
            ]],
            'metode_pembelajaran' => [
                ['label' => 'Diskusi', 'rata_rata' => 1.6, 'n' => 800],
                ['label' => 'Seminar', 'rata_rata' => 2.9, 'n' => 800],
            ],
        ], $overrides);
    }

    private function analyze(array $current, ?array $previous = null, ?array $breakdown = null, ?array $previousBreakdown = null, array $faculties = []): array
    {
        return (new ReportAnalysisService)->analyze(
            2025,
            $current,
            $previous,
            $breakdown ?? $this->breakdown(),
            $previousBreakdown,
            collect($faculties),
            ['jumlah_respon' => 0, 'per_pertanyaan' => []],
        );
    }

    public function test_ratings_follow_the_criteria(): void
    {
        $analysis = $this->analyze($this->current());

        $this->assertStringContainsString('Tingkat respon 85,0% tergolong sangat baik', $analysis['responden'][0]);
        $this->assertStringContainsString('83,3%, tergolong sangat baik', $analysis['masa_tunggu'][0]);
        $this->assertStringContainsString('71,4% lulusan menilai pekerjaannya relevan', $analysis['f14'][0]);
    }

    public function test_a_low_response_rate_gets_a_recommendation_and_an_iku_warning(): void
    {
        $analysis = $this->analyze($this->current(['persentase_responden' => 35.0]));

        $this->assertStringContainsString('tergolong kurang', $analysis['responden'][0]);
        $this->assertStringContainsString('pengingat berkala', end($analysis['responden']));
        $this->assertStringContainsString('menaikkan respon', end($analysis['iku']));
    }

    public function test_changes_are_compared_with_the_previous_year(): void
    {
        $previous = $this->current(['persentase_responden' => 70.0, 'iku_berdasar_lulusan' => 60.0]);
        $previousBreakdown = $this->breakdown(['rata_rata_pendapatan' => 5_000_000]);

        $analysis = $this->analyze($this->current(), $previous, null, $previousBreakdown);

        $this->assertStringContainsString('naik 15,0 poin dibanding 2024 (70,0%)', $analysis['responden'][1]);
        $this->assertStringContainsString('turun 5,0 poin dibanding 2024', $analysis['iku'][1]);
        $this->assertStringContainsString('Naik 20,0% dibanding 2024', $analysis['pendapatan'][1]);
    }

    public function test_weak_spots_are_flagged(): void
    {
        $analysis = $this->analyze($this->current(), null, null, null, [
            ['faculty' => 'Hukum', 'persentase_responden' => 36.1],
            ['faculty' => 'Teknik', 'persentase_responden' => 90.0],
        ]);

        $this->assertStringContainsString('Ketimpangan ini cukup besar', $analysis['fakultas'][0]);
        $this->assertStringContainsString('Hukum (36,1%)', $analysis['fakultas'][1]);
        $this->assertStringContainsString('Peran kampus sebagai saluran informasi kerja masih kecil (4,3%)', $analysis['cara_mencari_kerja'][1]);
        $this->assertStringContainsString('termasuk 5 orang di luar negeri', $analysis['tempat_kerja'][1]);
        $this->assertStringContainsString('paling kurang ditekankan adalah seminar', $analysis['metode'][0]);
    }

    public function test_sections_without_data_are_left_out(): void
    {
        $analysis = $this->analyze($this->current(), null, $this->breakdown([
            'f14' => ['Sangat relevan' => 0, 'Relevan' => 0, 'Cukup relevan' => 0, 'Kurang relevan' => 0, 'Tidak relevan' => 0],
            'tempat_kerja' => [],
        ]));

        $this->assertArrayNotHasKey('f14', $analysis);
        $this->assertArrayNotHasKey('tempat_kerja', $analysis);
        $this->assertArrayNotHasKey('pengguna_alumni', $analysis);
    }
}
