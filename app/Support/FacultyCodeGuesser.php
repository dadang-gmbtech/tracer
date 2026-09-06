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

    /**
     * Real exported files (Excel/CSV) routinely carry a kode_fakultas value
     * with invisible formatting noise — trailing spaces, a stray carriage
     * return, a non-breaking space — that's invisible in a spreadsheet cell
     * but makes "A" and "A " two different strings to a database unique
     * constraint. Left unnormalized, every import creates a brand-new
     * faculty instead of reusing the existing one for that code. Strips all
     * whitespace (not just the ends) and uppercases, so "A", " A", "a\r",
     * "A\u{A0}" all collapse to the same "A".
     *
     * Also rejects values that couldn't possibly be a real faculty code
     * (returning null instead) — a misaligned column in a source file has
     * been seen putting a student's full email address in kodefak, which
     * would otherwise get accepted at face value and create a garbage
     * faculty. A rejected value falls through to the NIM-based guess
     * instead, same as a genuinely blank column.
     */
    public static function normalize(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $normalized = preg_replace('/[\s\x{00A0}]+/u', '', $code);

        if ($normalized === '' || str_contains($normalized, '@') || mb_strlen($normalized) > 10) {
            return null;
        }

        return strtoupper($normalized);
    }
}
