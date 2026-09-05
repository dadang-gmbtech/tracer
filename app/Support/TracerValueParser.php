<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Defensive parsing for the Kemdiktisaintek tracer export/import format —
 * real submissions mix clean numeric answers with free text ("50>", "~20",
 * "Juli", etc.), so every cast falls back to null instead of throwing.
 * Shared by MockApiSeeder and TracerResponsesImport.
 */
class TracerValueParser
{
    public static function str(mixed $v): ?string
    {
        $v = trim((string) $v);

        return $v === '' ? null : $v;
    }

    public static function int(mixed $v): ?int
    {
        $v = trim((string) $v);
        if ($v === '' || ! preg_match('/-?\d+/', $v, $m)) {
            return null;
        }

        return (int) $m[0];
    }

    public static function decimal(mixed $v): ?float
    {
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }

        $v = str_replace(',', '.', $v);
        if (! preg_match('/-?\d+(\.\d+)?/', $v, $m)) {
            return null;
        }

        return (float) $m[0];
    }

    public static function bool(mixed $v): ?bool
    {
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }

        return $v === '1';
    }

    public static function date(mixed $v): ?string
    {
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }

        try {
            return Carbon::parse($v)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }
}
