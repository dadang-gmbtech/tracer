<?php

namespace App\Support;

/**
 * Approximate geographic centroids for Indonesia's 38 BPS-coded provinces
 * (see ProvinceCitySeeder for the code -> name list this matches), used to
 * plot alumni distribution as proportional circle markers on a map. These
 * are rough visual centroids for that purpose only — not surveyed
 * administrative boundaries. Code '99' (Luar Negeri) has no coordinate on
 * purpose: alumni working abroad aren't a single pinnable point on a map of
 * Indonesia.
 */
class ProvinceCentroids
{
    /**
     * @var array<string, array{0: float, 1: float}> [lat, lng] keyed by BPS province code
     */
    public const COORDINATES = [
        '11' => [4.695, 96.749],     // Aceh
        '12' => [2.115, 99.545],     // Sumatera Utara
        '13' => [-0.740, 100.800],   // Sumatera Barat
        '14' => [0.293, 101.706],    // Riau
        '15' => [-1.610, 103.610],   // Jambi
        '16' => [-3.319, 103.914],   // Sumatera Selatan
        '17' => [-3.795, 102.259],   // Bengkulu
        '18' => [-4.558, 105.407],   // Lampung
        '19' => [-2.741, 106.440],   // Kepulauan Bangka Belitung
        '21' => [3.945, 108.142],    // Kepulauan Riau
        '31' => [-6.208, 106.845],   // D.K.I. Jakarta
        '32' => [-6.914, 107.609],   // Jawa Barat
        '33' => [-7.150, 110.140],   // Jawa Tengah
        '34' => [-7.797, 110.370],   // D.I. Yogyakarta
        '35' => [-7.536, 112.238],   // Jawa Timur
        '36' => [-6.405, 106.064],   // Banten
        '51' => [-8.409, 115.189],   // Bali
        '52' => [-8.653, 117.361],   // Nusa Tenggara Barat
        '53' => [-8.657, 121.079],   // Nusa Tenggara Timur
        '61' => [-0.278, 111.475],   // Kalimantan Barat
        '62' => [-1.681, 113.382],   // Kalimantan Tengah
        '63' => [-3.092, 115.283],   // Kalimantan Selatan
        '64' => [0.538, 116.419],    // Kalimantan Timur
        '65' => [3.073, 116.041],    // Kalimantan Utara
        '71' => [1.474, 124.842],    // Sulawesi Utara
        '72' => [-1.430, 121.446],   // Sulawesi Tengah
        '73' => [-3.669, 119.974],   // Sulawesi Selatan
        '74' => [-4.145, 122.174],   // Sulawesi Tenggara
        '75' => [0.699, 122.446],    // Gorontalo
        '76' => [-2.844, 119.232],   // Sulawesi Barat
        '81' => [-3.238, 130.145],   // Maluku
        '82' => [0.790, 127.378],    // Maluku Utara
        '91' => [-4.269, 138.080],   // Papua
        '92' => [-1.336, 133.174],   // Papua Barat
        '93' => [-7.400, 139.400],   // Papua Selatan
        '94' => [-3.900, 136.300],   // Papua Tengah
        '95' => [-4.100, 138.900],   // Papua Pegunungan
        '96' => [-1.000, 131.200],   // Papua Barat Daya
    ];

    /**
     * @return array{0: float, 1: float}|null
     */
    public static function forCode(string $code): ?array
    {
        return self::COORDINATES[$code] ?? null;
    }
}
