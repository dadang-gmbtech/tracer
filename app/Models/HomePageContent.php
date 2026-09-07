<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton (always id=1) holding the editable text on the public landing
 * page (see resources/views/home.blade.php) — hero copy, the "Tentang"
 * cards, the "Alur Pengisian" steps, and the footer address. Icons and
 * layout stay in the Blade template; only the wording is admin-editable.
 */
class HomePageContent extends Model
{
    protected $fillable = [
        'hero_title', 'hero_subtitle', 'hero_description',
        'tentang_description', 'tentang_cards', 'alur_steps',
        'footer_address',
    ];

    protected function casts(): array
    {
        return [
            'tentang_cards' => 'array',
            'alur_steps' => 'array',
        ];
    }

    /**
     * The one row this model ever has, creating it with the site's original
     * copy the first time anything asks for it.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], self::defaults());
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'hero_title' => 'Sistem Tracer Studi',
            'hero_subtitle' => 'Universitas Jenderal Soedirman',
            'hero_description' => 'Kami mengundang seluruh alumni untuk mengisi kuesioner tracer studi. Masukan Anda membantu universitas mengevaluasi capaian pembelajaran, menjaga relevansi kurikulum dengan dunia kerja, dan mendukung proses akreditasi program studi.',
            'tentang_description' => 'Tracer studi adalah penelusuran terhadap alumni untuk mengetahui perjalanan karier mereka setelah lulus — masa tunggu kerja, kesesuaian bidang kerja dengan program studi, hingga penilaian pengguna lulusan. Data ini menjadi bahan evaluasi mutu pendidikan dan salah satu syarat akreditasi institusi maupun program studi.',
            'tentang_cards' => [
                ['title' => 'Evaluasi Kurikulum', 'description' => 'Menilai relevansi materi perkuliahan dengan kebutuhan dunia kerja.'],
                ['title' => 'Penjaminan Mutu', 'description' => 'Menjadi indikator capaian pembelajaran dan mutu lulusan.'],
                ['title' => 'Akreditasi', 'description' => 'Mendukung data wajib akreditasi institusi dan program studi.'],
            ],
            'alur_steps' => [
                'Klik tombol Login di halaman ini.',
                'Masuk memakai akun SSO UNSOED, atau NIM & Tanggal Lahir.',
                'Isi seluruh pertanyaan pada formulir tracer studi.',
                'Kirim jawaban Anda — data otomatis tersimpan.',
            ],
            'footer_address' => 'Jl. Dr. Suparno, Karangwangkal, Purwokerto Utara, Banyumas, Jawa Tengah',
        ];
    }
}
