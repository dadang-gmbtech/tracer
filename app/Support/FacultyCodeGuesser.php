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
    public static function guess(string $nim, Collection $facultiesByCode): ?string
    {
        $code = strtoupper(substr(trim($nim), 0, 1));

        return $facultiesByCode->has($code) ? $code : null;
    }
}
