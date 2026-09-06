<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * UNSOED's NIM convention embeds the faculty code as the NIM's first
 * character (e.g. A1A021001 -> Faculty "A" = Pertanian, H1A021023 ->
 * Faculty "H" = Teknik, L1A018015 -> Faculty "L" = Perikanan dan Ilmu
 * Kelautan). Used as a last-resort fallback in the alumni/tracer imports
 * when a file has no kode_fakultas column, the program studi isn't
 * registered yet, and no faculty was picked on the import form.
 *
 * Only trusted when the guessed single letter matches a Faculty that
 * already exists — it never invents a new faculty code, so a wrong guess
 * just falls through to the normal "not found" skip instead of silently
 * creating bad master data.
 */
class FacultyCodeGuesser
{
    /**
     * UNSOED's fixed faculty code -> name directory, used as a fallback
     * whenever an import creates a new faculty but the file itself doesn't
     * carry a proper name for it (e.g. a national-format file that only has
     * kodefak, not namafakultas) — without this, the faculty would silently
     * be saved with its single-letter code as its "name".
     *
     * @var array<string, string>
     */
    public const NAMES = [
        'A' => 'Pertanian',
        'B' => 'Biologi',
        'C' => 'Ekonomi dan Bisnis',
        'D' => 'Peternakan',
        'E' => 'Hukum',
        'F' => 'Ilmu Sosial dan Ilmu Politik',
        'G' => 'Kedokteran',
        'H' => 'Teknik',
        'I' => 'Ilmu-ilmu Kesehatan',
        'J' => 'Ilmu Budaya',
        'K' => 'Matematika dan Ilmu Pengetahuan Alam',
        'L' => 'Perikanan dan Ilmu Kelautan',
    ];

    public static function guess(string $nim, Collection $facultiesByCode): ?string
    {
        $code = strtoupper(substr(trim($nim), 0, 1));

        return $facultiesByCode->has($code) ? $code : null;
    }

    public static function name(string $code): ?string
    {
        return self::NAMES[strtoupper($code)] ?? null;
    }
}
