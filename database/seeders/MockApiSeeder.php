<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Province;
use App\Models\User;
use App\Support\TracerFieldCodes;
use App\Support\TracerValueParser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds realistic demo data from database/data/mock_students.csv — an actual
 * export from the national Kemdiktisaintek tracer system (see Contoh data.csv),
 * so the dashboard/IKU/export screens have real numbers to show out of the box.
 */
class MockApiSeeder extends Seeder
{
    public function run(): void
    {
        $file = database_path('data/mock_students.csv');
        if (! file_exists($file)) {
            return;
        }

        $handle = fopen($file, 'r');
        $header = fgetcsv($handle, 0, ';');

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) !== count($header)) {
                continue;
            }

            $data = array_combine($header, $row);
            $this->importRow($data);
        }

        fclose($handle);
    }

    private function importRow(array $data): void
    {
        DB::table('faculties')->updateOrInsert(
            ['code' => trim($data['kodefak'])],
            ['name' => trim($data['namafakultas']), 'updated_at' => now(), 'created_at' => now()]
        );
        $facultyId = DB::table('faculties')->where('code', trim($data['kodefak']))->value('id');

        DB::table('program_studies')->updateOrInsert(
            ['code' => trim($data['kodeprog'])],
            [
                'faculty_id' => $facultyId,
                'name' => trim($data['namaprogdikti']),
                'level' => trim($data['namajenjang']),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $prodiId = DB::table('program_studies')->where('code', trim($data['kodeprog']))->value('id');

        $nim = trim($data['nimhsmsmh']);
        $email = trim($data['emailmsmh']) ?: ($nim.'@mhs.unsoed.ac.id');

        $user = User::updateOrCreate(
            ['nim' => $nim],
            [
                'name' => trim($data['nmmhsmsmh']),
                'email' => $email,
                'password' => bcrypt('password'),
                'tanggal_lahir' => '2000-01-01',
                'no_telp' => trim($data['telpomsmh']) ?: null,
                'status' => 'active',
            ]
        );

        if (! $user->hasRole('Alumni')) {
            $user->assignRole('Alumni');
        }

        DB::table('alumni')->updateOrInsert(
            ['nim' => $nim],
            [
                'nama' => trim($data['nmmhsmsmh']),
                'email' => $email,
                'faculty_id' => $facultyId,
                'program_study_id' => $prodiId,
                'graduation_year' => (int) trim($data['tahun_lulus']),
                'nik' => trim($data['nik']) ?: null,
                'npwp' => trim($data['npwp']) ?: null,
                'phone' => trim($data['telpomsmh']) ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $alumniId = DB::table('alumni')->where('nim', $nim)->value('id');

        // Rows with f8 empty never opened/submitted the tracer form.
        if (trim($data['f8']) === '') {
            return;
        }

        $workProvinceId = $this->resolveProvince($data['propinsi_tempat_bekerja'] ?? null);
        $workCityId = $this->resolveCity($workProvinceId, $data['kabupaten_tempat_bekerja'] ?? null);

        $tracer = [
            'alumni_id' => $alumniId,
            'work_province_id' => $workProvinceId,
            'work_city_id' => $workCityId,
            'f504' => TracerValueParser::str($data['f504'] ?? null),
            'submitted_by_user_id' => $user->id,
            'submitted_at' => TracerValueParser::date($data['waktu_update'] ?? null) ?? now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach (TracerFieldCodes::codes() as $code) {
            $tracer[$code] = match (true) {
                in_array($code, TracerFieldCodes::decimalCodes(), true) => TracerValueParser::decimal($data[$code] ?? null),
                in_array($code, TracerFieldCodes::integerCodes(), true) => TracerValueParser::int($data[$code] ?? null),
                in_array($code, TracerFieldCodes::booleanCodes(), true) => TracerValueParser::bool($data[$code] ?? null),
                default => TracerValueParser::str($data[$code] ?? null),
            };
        }

        DB::table('tracer_responses')->updateOrInsert(['alumni_id' => $alumniId], $tracer);
    }

    private function resolveProvince(?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $clean = trim(str_replace('Prov.', '', $name));

        return Province::firstOrCreate(
            ['name' => $clean],
            ['code' => 'X'.substr(md5($clean), 0, 6)]
        )->id;
    }

    private function resolveCity(?int $provinceId, ?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '' || ! $provinceId) {
            return null;
        }

        return City::firstOrCreate(
            ['province_id' => $provinceId, 'name' => $name]
        )->id;
    }
}
