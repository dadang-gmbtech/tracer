<?php

namespace App\Support;

/**
 * Single source of truth for the Kemdiktisaintek tracer field codes, shared
 * by TracerResponsesExport and the tracer import so their column order can
 * never drift apart. f504 is intentionally excluded (undocumented field,
 * dropped from both import and export per admin request).
 */
class TracerFieldCodes
{
    /** @return list<string> all f-codes, in export/import column order */
    public static function codes(): array
    {
        return array_map(
            fn ($code) => "f{$code}",
            [
                8, 502, 505, 506, '5a1', '5a2', 1101, 1102, '5b', '5c', '5d',
                '18a', '18b', '18c', '18d', 1201, 1202, 14, 15,
                ...range(1761, 1774), ...range(21, 27),
                301, 302, 303, ...range(401, 416),
                6, 7, '7a', 1001, 1002, ...range(1601, 1614),
            ]
        );
    }

    /** @return list<string> full export/import column header order */
    public static function exportColumns(): array
    {
        return [
            'kdptimsmh', 'kdpstmsmh', 'nimhsmsmh', 'nmmhsmsmh', 'telpomsmh', 'emailmsmh',
            'tahun_lulus', 'nik', 'npwp',
            ...self::codes(),
            'emailunsoed', 'kodefak', 'namafakultas', 'kodeprog', 'namajenjang', 'namaprogdikti',
            'waktu_update', 'propinsi_tempat_bekerja', 'kabupaten_tempat_bekerja',
            'datarespondendikti_id', 'idkuesionerdikti',
        ];
    }

    /** Small select/rating codes (1-5 scale or short enumerated options). */
    public static function integerCodes(): array
    {
        return array_map(fn ($code) => "f{$code}", [
            8, 502, 1101, '5c', '5d', '18a', 1201, 14, 15, 301, 302, 303, 6, 7, '7a', 1001,
            ...range(1761, 1774), ...range(21, 27),
        ]);
    }

    /** Free-text checkbox flags (0/1). */
    public static function booleanCodes(): array
    {
        return array_map(fn ($code) => "f{$code}", [...range(401, 415), ...range(1601, 1613)]);
    }

    /** Monetary field(s). */
    public static function decimalCodes(): array
    {
        return ['f505'];
    }
}
