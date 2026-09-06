<?php

namespace App\Support;

use App\Models\User;

/**
 * Shared "may this user bootstrap a new alumni/tracer row for this
 * faculty/prodi code" check, used by AlumniImport and TracerResponsesImport
 * when the row's NIM doesn't exist yet (so there's no existing Alumni record
 * to run the normal fillTracer policy against).
 */
class ImportScopeGuard
{
    public static function allows(User $actor, ?string $facultyCode, ?string $prodiCode): bool
    {
        if ($actor->hasAnyRole(['Super Admin', 'Admin Universitas'])) {
            return true;
        }

        if ($actor->hasRole('Admin Prodi')) {
            return $prodiCode !== null && $actor->studyProgram && $actor->studyProgram->code === $prodiCode;
        }

        if ($actor->hasAnyRole(['Admin Fakultas', 'Surveyor'])) {
            return $facultyCode !== null && $actor->faculty && $actor->faculty->code === $facultyCode;
        }

        return false;
    }
}
