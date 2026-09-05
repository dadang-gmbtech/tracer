<?php

namespace App\Services;

use App\Contracts\AcademicInfoServiceContract;
use App\DataTransferObjects\AcademicIdentity;
use App\Models\Alumni;

/**
 * Development stand-in for the campus academic information system. Sources
 * the identity block from our own `alumni` table (itself seeded from a real
 * Kemdiktisaintek export — see MockApiSeeder) instead of calling an external
 * SIAKAD/PDDikti API. Replace the binding in AppServiceProvider with a real
 * HTTP client once that API is available.
 */
class MockAcademicInfoService implements AcademicInfoServiceContract
{
    public function forAlumni(Alumni $alumni): AcademicIdentity
    {
        return new AcademicIdentity(
            nim: $alumni->nim,
            namaMahasiswa: $alumni->nama,
            kodePt: config('tracer.kode_pt'),
            kodeProdi: $alumni->studyProgram?->code,
            tahunLulus: $alumni->graduation_year,
            noTelp: $alumni->phone,
            email: $alumni->email,
            nik: $alumni->nik,
            npwp: $alumni->npwp,
        );
    }
}
