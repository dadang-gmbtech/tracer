<?php

namespace App\Support;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

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

    /**
     * Accepts a date cell as Excel hands it over (a serial number such as
     * 45884.4375), Indonesian day-first text (15/08/2025, optionally with a
     * time), or anything Carbon can parse (2025-08-15 10:30:00).
     */
    public static function date(mixed $v): ?string
    {
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }

        try {
            if (is_numeric($v) && (float) $v >= 20000 && (float) $v < 100000) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v))->toDateTimeString();
            }

            if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$#', $v, $m)) {
                if (! checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
                    return null;
                }

                return Carbon::create((int) $m[3], (int) $m[2], (int) $m[1], (int) ($m[4] ?? 0), (int) ($m[5] ?? 0), (int) ($m[6] ?? 0))
                    ->toDateTimeString();
            }

            return Carbon::parse($v)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }
}
