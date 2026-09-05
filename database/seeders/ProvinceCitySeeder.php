<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

class ProvinceCitySeeder extends Seeder
{
    /**
     * All 38 current Indonesian provinces (BPS codes) plus a "Luar Negeri"
     * pseudo-province for alumni working abroad. Cities/regencies are not
     * pre-seeded (~514 nationwide) — MasterData CRUD + Excel import
     * (Admin\CityController) let admins add what they need; MockApiSeeder
     * also creates cities on the fly for the rows in the demo dataset.
     */
    public function run(): void
    {
        $provinces = [
            '11' => 'Aceh', '12' => 'Sumatera Utara', '13' => 'Sumatera Barat', '14' => 'Riau',
            '15' => 'Jambi', '16' => 'Sumatera Selatan', '17' => 'Bengkulu', '18' => 'Lampung',
            '19' => 'Kepulauan Bangka Belitung', '21' => 'Kepulauan Riau', '31' => 'D.K.I. Jakarta',
            '32' => 'Jawa Barat', '33' => 'Jawa Tengah', '34' => 'D.I. Yogyakarta', '35' => 'Jawa Timur',
            '36' => 'Banten', '51' => 'Bali', '52' => 'Nusa Tenggara Barat', '53' => 'Nusa Tenggara Timur',
            '61' => 'Kalimantan Barat', '62' => 'Kalimantan Tengah', '63' => 'Kalimantan Selatan',
            '64' => 'Kalimantan Timur', '65' => 'Kalimantan Utara', '71' => 'Sulawesi Utara',
            '72' => 'Sulawesi Tengah', '73' => 'Sulawesi Selatan', '74' => 'Sulawesi Tenggara',
            '75' => 'Gorontalo', '76' => 'Sulawesi Barat', '81' => 'Maluku', '82' => 'Maluku Utara',
            '91' => 'Papua', '92' => 'Papua Barat', '93' => 'Papua Selatan', '94' => 'Papua Tengah',
            '95' => 'Papua Pegunungan', '96' => 'Papua Barat Daya',
        ];

        foreach ($provinces as $code => $name) {
            Province::updateOrCreate(['code' => $code], ['name' => $name]);
        }

        Province::updateOrCreate(['code' => '99'], ['name' => 'Luar Negeri']);
    }
}
