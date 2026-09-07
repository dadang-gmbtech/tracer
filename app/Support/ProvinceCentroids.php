<?php

namespace App\Support;

/**
 * Approximate geographic centroids for Indonesia's 38 provinces, used to plot
 * alumni distribution as proportional circle markers on a map. Keyed by
 * normalized province NAME rather than code: different tracer data sources
 * (the original ProvinceCitySeeder vs. rows created by import fallbacks) have
 * ended up storing different code schemes in production, but the province
 * names are consistent across all of them. These are rough visual centroids
 * for that purpose only — not surveyed administrative boundaries.
 *
 * "Luar Negeri" (alumni working abroad) has no coordinate on purpose: it
 * isn't a single pinnable point on a map of Indonesia.
 */
class ProvinceCentroids
{
    /**
     * @var array<string, array{0: float, 1: float}> [lat, lng] keyed by normalized (uppercased, trimmed) province name
     */
    public const COORDINATES = [
        'ACEH' => [4.695, 96.749],
        'SUMATERA UTARA' => [2.115, 99.545],
        'SUMATERA BARAT' => [-0.740, 100.800],
        'RIAU' => [0.293, 101.706],
        'JAMBI' => [-1.610, 103.610],
        'SUMATERA SELATAN' => [-3.319, 103.914],
        'BENGKULU' => [-3.795, 102.259],
        'LAMPUNG' => [-4.558, 105.407],
        'KEPULAUAN BANGKA BELITUNG' => [-2.741, 106.440],
        'KEPULAUAN RIAU' => [3.945, 108.142],
        'D.K.I. JAKARTA' => [-6.208, 106.845],
        'DKI JAKARTA' => [-6.208, 106.845],
        'JAWA BARAT' => [-6.914, 107.609],
        'JAWA TENGAH' => [-7.150, 110.140],
        'D.I. YOGYAKARTA' => [-7.797, 110.370],
        'DI YOGYAKARTA' => [-7.797, 110.370],
        'DAERAH ISTIMEWA YOGYAKARTA' => [-7.797, 110.370],
        'JAWA TIMUR' => [-7.536, 112.238],
        'BANTEN' => [-6.405, 106.064],
        'BALI' => [-8.409, 115.189],
        'NUSA TENGGARA BARAT' => [-8.653, 117.361],
        'NUSA TENGGARA TIMUR' => [-8.657, 121.079],
        'KALIMANTAN BARAT' => [-0.278, 111.475],
        'KALIMANTAN TENGAH' => [-1.681, 113.382],
        'KALIMANTAN SELATAN' => [-3.092, 115.283],
        'KALIMANTAN TIMUR' => [0.538, 116.419],
        'KALIMANTAN UTARA' => [3.073, 116.041],
        'SULAWESI UTARA' => [1.474, 124.842],
        'SULAWESI TENGAH' => [-1.430, 121.446],
        'SULAWESI SELATAN' => [-3.669, 119.974],
        'SULAWESI TENGGARA' => [-4.145, 122.174],
        'GORONTALO' => [0.699, 122.446],
        'SULAWESI BARAT' => [-2.844, 119.232],
        'MALUKU' => [-3.238, 130.145],
        'MALUKU UTARA' => [0.790, 127.378],
        'PAPUA' => [-4.269, 138.080],
        'PAPUA BARAT' => [-1.336, 133.174],
        'PAPUA SELATAN' => [-7.400, 139.400],
        'PAPUA TENGAH' => [-3.900, 136.300],
        'PAPUA PEGUNUNGAN' => [-4.100, 138.900],
        'PAPUA BARAT DAYA' => [-1.000, 131.200],
    ];

    /**
     * @return array{0: float, 1: float}|null
     */
    public static function forName(?string $name): ?array
    {
        if ($name === null) {
            return null;
        }

        return self::COORDINATES[self::normalize($name)] ?? null;
    }

    public static function isLuarNegeri(?string $name): bool
    {
        return self::normalize($name ?? '') === 'LUAR NEGERI';
    }

    private static function normalize(string $name): string
    {
        return mb_strtoupper(trim($name));
    }
}
