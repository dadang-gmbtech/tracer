<?php

namespace App\Console\Commands;

use App\Models\Faculty;
use App\Support\FacultyCodeGuesser;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Repairs faculties that were bootstrapped by an import without a proper
 * name — they were saved with their single-letter code as the name (e.g.
 * "A" instead of "Pertanian") because the source file only had kodefak,
 * not namafakultas. Only touches rows whose name still matches their own
 * code, so a faculty that was renamed on purpose is left alone.
 */
#[Signature('app:fix-faculty-names {--dry-run : Only list what would change, without saving}')]
#[Description('Backfill faculty names that were saved as their own code (e.g. "A") from the known UNSOED directory')]
class FixFacultyNames extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $fixed = 0;

        foreach (Faculty::all() as $faculty) {
            if (strcasecmp($faculty->name, $faculty->code) !== 0) {
                continue;
            }

            $correctName = FacultyCodeGuesser::name($faculty->code);

            if ($correctName === null || $correctName === $faculty->name) {
                continue;
            }

            $this->line("Fakultas {$faculty->code}: \"{$faculty->name}\" -> \"{$correctName}\"");

            if (! $dryRun) {
                $faculty->update(['name' => $correctName]);
            }

            $fixed++;
        }

        $this->info($dryRun ? "{$fixed} fakultas akan diperbaiki (dry run, tidak disimpan)." : "{$fixed} fakultas diperbaiki.");

        return self::SUCCESS;
    }
}
