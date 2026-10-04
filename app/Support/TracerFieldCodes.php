<?php

namespace App\Support;

/**
 * Single source of truth for the Kemdiktisaintek tracer field codes, shared
 * by TracerResponsesExport and the tracer import so their column order can
 * never drift apart. Column order matches the official "Pelaporan Tracer
 * Study" reporting template exactly, so an export can be uploaded straight
 * to the national system and an edited copy can be re-imported here. f504
 * and f506 are intentionally excluded — they aren't part of that template.
 */
class TracerFieldCodes
{
    /** @return list<string> all f-codes, in export/import column order */
    public static function codes(): array
    {
        return array_map(
            fn ($code) => "f{$code}",
            [
                8, 502, 505, '5c', '18a', '18b', '18c', '18d', '5b',
                '5a0', '5a1', '5a2', '5e', 1101, 1102, '5d', 14, 15,
                301, 302, 303, ...range(401, 409), 411, 413, 414, 415, 416,
                6, 7, '7a', 1001, 1002,
                ...range(1601, 1610), 1612, 1613, 1614,
                1201, 1202,
                1761, 1763, 1765, 1767, 1769, 1771, 1773, 1775, 1777, 1779, 1781,
                1762, 1764, 1766, 1768, 1770, 1772, 1774, 1776, 1778, 1780, 1782,
                ...range(21, 37),
            ]
        );
    }

    /** @return list<string> full export/import column header order */
    public static function exportColumns(): array
    {
        return [
            'Kode PT', 'Kode Prodi', 'NIM/Nomor Mhs', 'Nama Mhs', 'Nomor HP Mhs', 'Email Mhs',
            "Tahun Lulus\n Keluar", 'NIK', 'NPWP',
            ...array_map('strtoupper', self::codes()),
        ];
    }

    /** Small select/rating codes (1-5 scale or short enumerated options). */
    public static function integerCodes(): array
    {
        return array_map(fn ($code) => "f{$code}", [
            8, 502, 1101, '5c', '5d', '5e', '18a', 1201, 14, 15, 301, 302, 303, 6, 7, '7a', 1001,
            ...range(1761, 1782), ...range(21, 37),
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
