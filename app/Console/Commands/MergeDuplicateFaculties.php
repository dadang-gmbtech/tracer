<?php

namespace App\Console\Commands;

use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\User;
use App\Support\FacultyCodeGuesser;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Merges faculty rows that only look distinct because their kode_fakultas
 * carried invisible formatting noise (a trailing space, a stray carriage
 * return, a non-breaking space) — e.g. "A" and "A " both satisfy
 * faculties.code's unique constraint as different strings, so a bulk import
 * kept creating a fresh faculty instead of reusing the existing one for
 * that code. Faculties are grouped by FacultyCodeGuesser::normalize(); all
 * but one per group are merged into a single canonical row, re-pointing
 * every study_programs/alumni/users row that referenced a duplicate before
 * deleting it.
 */
#[Signature('app:merge-duplicate-faculties {--dry-run : Only list what would change, without saving}')]
#[Description('Merge faculty rows whose code only differs by whitespace/case into one canonical row per faculty')]
class MergeDuplicateFaculties extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $groups = Faculty::all()->groupBy(fn (Faculty $f) => FacultyCodeGuesser::normalize($f->code));
        $mergedCount = 0;

        foreach ($groups as $normalizedCode => $duplicates) {
            if ($duplicates->count() < 2) {
                continue;
            }

            // Compare against the *normalized* code, not the row's own raw
            // code — a row like code:"H ", name:"H" has a bare-code name in
            // spirit, but "H" !== "H " would wrongly read as a real name.
            $canonical = $duplicates->first(fn (Faculty $f) => $f->name !== $normalizedCode) ?? $duplicates->sortBy('id')->first();
            $others = $duplicates->reject(fn (Faculty $f) => $f->id === $canonical->id);
            $canonicalNameIsBareCode = $canonical->name === $normalizedCode;

            $this->line("Fakultas {$normalizedCode}: menggabungkan {$others->count()} duplikat ke #{$canonical->id} ({$canonical->name})");

            if ($dryRun) {
                $mergedCount += $others->count();

                continue;
            }

            DB::transaction(function () use ($canonical, $others, $normalizedCode, $canonicalNameIsBareCode) {
                foreach ($others as $duplicate) {
                    StudyProgram::where('faculty_id', $duplicate->id)->update(['faculty_id' => $canonical->id]);
                    Alumni::where('faculty_id', $duplicate->id)->update(['faculty_id' => $canonical->id]);
                    User::where('faculty_id', $duplicate->id)->update(['faculty_id' => $canonical->id]);
                    $duplicate->delete();
                }

                $canonical->update([
                    'code' => $normalizedCode,
                    'name' => $canonicalNameIsBareCode ? (FacultyCodeGuesser::name($normalizedCode) ?? $canonical->name) : $canonical->name,
                ]);
            });

            $mergedCount += $others->count();
        }

        $this->info($dryRun ? "{$mergedCount} fakultas duplikat akan digabungkan (dry run, tidak disimpan)." : "{$mergedCount} fakultas duplikat digabungkan.");

        return self::SUCCESS;
    }
}
