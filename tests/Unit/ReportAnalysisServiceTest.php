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
            'kompetensi' => [
                ['label' => 'Etika', 'saat_lulus' => 4.5, 'dibutuhkan' => 4.6, 'selisih' => 0.1, 'n' => 800],
                ['label' => 'Bahasa Inggris', 'saat_lulus' => 3.4, 'dibutuhkan' => 4.1, 'selisih' => 0.7, 'n' => 800],
            ],
            'iku' => [
                'lulusan' => 900, 'tidak_mengisi' => 150, 'bobot_total' => 495.0, 'nilai' => 55.0,
                'komponen' => [
                    'bekerja' => ['jumlah' => 550, 'bobot' => 400.0], 'wiraswasta' => ['jumlah' => 40, 'bobot' => 35.0],
                    'studi_lanjut' => ['jumlah' => 100, 'bobot' => 60.0], 'lainnya' => ['jumlah' => 60, 'bobot' => 0.0],
                ],
                'bekerja_wiraswasta' => 590, 'gaji_di_atas_ump' => 300, 'tanpa_data_ump' => 90, 'masa_tunggu_tidak_dihitung' => 20,
            ],
        ], $overrides);
    }

    /**
     * @param  array<int, array{0: array, 1: array}>  $years  year => [current overrides, breakdown overrides]
     */
    private function analyze(array $years, array $faculties = [], array $employer = ['jumlah_respon' => 0, 'per_pertanyaan' => []]): array
    {
        $history = [];
        foreach ($years as $year => [$current, $breakdown]) {
            $history[$year] = ['current' => $this->current($current), 'breakdown' => $this->breakdown($breakdown)];
        }

        return (new ReportAnalysisService)->analyze(max(array_keys($years)), $history, collect($faculties), $employer);
    }

    public function test_ratings_follow_the_criteria(): void
    {
        $sections = $this->analyze([2025 => [[], []]])['sections'];

        $this->assertStringContainsString('Tingkat respon 85,0% tergolong sangat baik', $sections['responden'][0]);
        $this->assertStringContainsString('83,3%, tergolong sangat baik', $sections['masa_tunggu'][0]);
        $this->assertStringContainsString('71,4% lulusan menilai pekerjaannya relevan', $sections['f14'][0]);
    }

    public function test_a_two_year_history_compares_with_the_year_before(): void
    {
        $sections = $this->analyze([
            2024 => [['persentase_responden' => 70.0, 'iku_berdasar_lulusan' => 60.0], ['rata_rata_pendapatan' => 5_000_000]],
            2025 => [[], []],
        ])['sections'];

        $this->assertStringContainsString('naik 15,0 poin dibanding 2024 (70,0%)', $sections['responden'][1]);
        $this->assertStringContainsString('turun 5,0 poin dibanding 2024', $sections['iku'][1]);
        $this->assertStringContainsString('naik 20,0% dibanding 2024', $sections['pendapatan'][1]);
    }

    public function test_a_longer_history_names_a_streak_or_the_range(): void
    {
        $sections = $this->analyze([
            2022 => [['persentase_responden' => 50.0, 'iku_berdasar_lulusan' => 40.0], []],
            2023 => [['persentase_responden' => 60.0, 'iku_berdasar_lulusan' => 58.0], []],
            2024 => [['persentase_responden' => 70.0, 'iku_berdasar_lulusan' => 45.0], []],
            2025 => [['persentase_responden' => 85.0, 'iku_berdasar_lulusan' => 50.0], []],
        ])['sections'];

        $this->assertStringContainsString('naik 3 tahun berturut-turut dari 50,0% (2022)', $sections['responden'][1]);
        $this->assertStringContainsString('berkisar antara 40,0% (2022) dan 58,0% (2023)', $sections['iku'][1]);
    }

    public function test_the_iku_section_explains_what_holds_it_down(): void
    {
        $iku = implode(' ', $this->analyze([2025 => [[], []]])['sections']['iku']);

        $this->assertStringContainsString('bekerja 400,00 (550 orang)', $iku);
        $this->assertStringContainsString('150 lulusan (16,7%) belum mengisi sehingga bernilai 0', $iku);
        $this->assertStringContainsString('60,0% lulusan yang bekerja/berwirausaha bergaji di atas 1,2 × UMP', $iku);
        $this->assertStringContainsString('90 lulusan bekerja di provinsi yang belum punya data UMP', $iku);
    }

    public function test_competency_gaps_are_named(): void
    {
        $kompetensi = implode(' ', $this->analyze([2025 => [[], []]])['sections']['kompetensi']);

        $this->assertStringContainsString('paling dikuasai lulusan saat lulus adalah etika', $kompetensi);
        $this->assertStringContainsString('bahasa inggris (+0,70)', $kompetensi);
    }

    public function test_the_executive_summary_puts_the_heaviest_concern_first(): void
    {
        $ringkasan = $this->analyze([2025 => [['persentase_responden' => 35.0, 'responden' => 350], []]], [
            ['faculty' => 'Hukum', 'persentase_responden' => 20.0],
            ['faculty' => 'Teknik', 'persentase_responden' => 50.0],
        ])['ringkasan'];

        $this->assertStringContainsString('350 dari 1.000 lulusan mengisi kuesioner (respon 35,0%)', $ringkasan['temuan'][0]);
        $this->assertStringContainsString('Tingkat respon baru 35,0%', $ringkasan['perhatian'][0]);
        $this->assertStringContainsString('Naikkan tingkat respon', $ringkasan['rekomendasi'][0]);
        $this->assertContains('Lengkapi data UMP per provinsi untuk tahun ini di menu Master Data agar IKU terhitung akurat.', $ringkasan['rekomendasi']);
        $this->assertLessThanOrEqual(5, count($ringkasan['rekomendasi']));
        $this->assertStringContainsString('Masa tunggu ≤6 bulan 83,3%', implode(' ', $ringkasan['baik']));
    }

    public function test_weak_spots_are_flagged(): void
    {
        $sections = $this->analyze([2025 => [[], []]], [
            ['faculty' => 'Hukum', 'persentase_responden' => 36.1],
            ['faculty' => 'Teknik', 'persentase_responden' => 90.0],
        ])['sections'];

        $this->assertStringContainsString('Ketimpangan ini cukup besar', $sections['fakultas'][0]);
        $this->assertStringContainsString('Hukum (36,1%)', $sections['fakultas'][1]);
        $this->assertStringContainsString('masih kecil (4,3%)', $sections['cara_mencari_kerja'][1]);
        $this->assertStringContainsString('termasuk 5 orang di luar negeri', $sections['tempat_kerja'][1]);
        $this->assertStringContainsString('paling kurang ditekankan adalah seminar', $sections['metode'][0]);
    }

    public function test_sections_without_data_are_left_out(): void
    {
        $sections = $this->analyze([2025 => [[], [
            'f14' => ['Sangat relevan' => 0, 'Relevan' => 0, 'Cukup relevan' => 0, 'Kurang relevan' => 0, 'Tidak relevan' => 0],
            'tempat_kerja' => [],
            'kompetensi' => [],
        ]]])['sections'];

        $this->assertArrayNotHasKey('f14', $sections);
        $this->assertArrayNotHasKey('tempat_kerja', $sections);
        $this->assertArrayNotHasKey('kompetensi', $sections);
        $this->assertArrayNotHasKey('pengguna_alumni', $sections);
    }
}
