<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Support\FacultyCodeGuesser;

/**
 * Find-or-create an Alumni (and its Faculty/StudyProgram, if new) from
 * identity data pulled out of an import row. Shared by AlumniImport and
 * TracerResponsesImport so a NIM not yet in the system can be bootstrapped
 * consistently from either upload.
 */
class AlumniProvisioningService
{
    /**
     * @param  array{nama?: ?string, email?: ?string, faculty_code: string, faculty_name?: ?string, prodi_code: string, prodi_name?: ?string, prodi_level?: ?string, graduation_year?: ?int, nik?: ?string, npwp?: ?string, phone?: ?string}  $identity
     */
    public function findOrCreate(string $nim, array $identity): Alumni
    {
        $faculty = Faculty::firstOrCreate(
            ['code' => $identity['faculty_code']],
            ['name' => $identity['faculty_name'] ?? FacultyCodeGuesser::name($identity['faculty_code']) ?? $identity['faculty_code']]
        );

        $studyProgram = StudyProgram::firstOrCreate(
            ['code' => $identity['prodi_code']],
            [
                'faculty_id' => $faculty->id,
                'name' => $identity['prodi_name'] ?? $identity['prodi_code'],
                'level' => $identity['prodi_level'] ?? '-',
            ]
        );

        return Alumni::updateOrCreate(
            ['nim' => $nim],
            [
                'nama' => $identity['nama'] ?? $nim,
                'email' => $identity['email'] ?? null,
                'faculty_id' => $faculty->id,
                'program_study_id' => $studyProgram->id,
                'graduation_year' => $identity['graduation_year'] ?? null,
                'nik' => $identity['nik'] ?? null,
                'npwp' => $identity['npwp'] ?? null,
                'phone' => $identity['phone'] ?? null,
            ]
        );
    }
}
