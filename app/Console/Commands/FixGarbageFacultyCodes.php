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
 * Repairs faculties whose `code` is garbage — e.g. a student's email address
 * instead of a real one-letter code — created when a source file's kodefak
 * column was misaligned/corrupted for some rows (the row's own value was
 * used as-is before FacultyCodeGuesser::normalize() started rejecting
 * implausible values). Each garbage row is resolved to the real faculty
 * either from its own `name` (when that already happens to be a bare
 * single-letter code, e.g. "L") or, failing that, from the NIM of the
 * alumni attached to it. A row that can't be resolved either way is
 * reported and left alone rather than guessed at.
 */
#[Signature('app:fix-garbage-faculty-codes {--dry-run : Only list what would change, without saving}')]
#[Description('Repair faculties whose code is garbage (e.g. an email address) by re-pointing their data to the real faculty and deleting the bad row')]
class FixGarbageFacultyCodes extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $fixed = 0;
        $unresolved = 0;

        foreach (Faculty::with('alumni:id,nim,faculty_id')->get() as $faculty) {
            if (! $this->looksGarbage($faculty->code)) {
                continue;
            }

            $targetCode = $this->resolveTargetCode($faculty);

            if ($targetCode === null) {
                $this->warn("Fakultas #{$faculty->id} (code=[{$faculty->code}], name=[{$faculty->name}]): tidak bisa ditentukan fakultas aslinya, dilewati.");
                $unresolved++;

                continue;
            }

            $this->line("Fakultas #{$faculty->id} (code=[{$faculty->code}], name=[{$faculty->name}]) -> {$targetCode}");

            if ($dryRun) {
                $fixed++;

                continue;
            }

            DB::transaction(function () use ($faculty, $targetCode) {
                $target = Faculty::firstOrCreate(
                    ['code' => $targetCode],
                    ['name' => FacultyCodeGuesser::name($targetCode) ?? $targetCode]
                );

                StudyProgram::where('faculty_id', $faculty->id)->update(['faculty_id' => $target->id]);
                Alumni::where('faculty_id', $faculty->id)->update(['faculty_id' => $target->id]);
                User::where('faculty_id', $faculty->id)->update(['faculty_id' => $target->id]);
                $faculty->delete();
            });

            $fixed++;
        }

        $this->info($dryRun
            ? "{$fixed} fakultas rusak akan diperbaiki, {$unresolved} tidak bisa ditentukan (dry run, tidak disimpan)."
            : "{$fixed} fakultas rusak diperbaiki, {$unresolved} tidak bisa ditentukan.");

        return self::SUCCESS;
    }

    private function looksGarbage(string $code): bool
    {
        return str_contains($code, '@') || mb_strlen($code) > 10;
    }

    private function resolveTargetCode(Faculty $faculty): ?string
    {
        $name = strtoupper(trim((string) $faculty->name));

        if (array_key_exists($name, FacultyCodeGuesser::NAMES)) {
            return $name;
        }

        foreach ($faculty->alumni as $alumnus) {
            $guess = strtoupper(substr(trim($alumnus->nim), 0, 1));

            if (array_key_exists($guess, FacultyCodeGuesser::NAMES)) {
                return $guess;
            }
        }

        return null;
    }
}
