<?php

namespace App\Contracts;

use App\DataTransferObjects\AcademicIdentity;
use App\Models\Alumni;

/**
 * Abstraction over the campus academic information system (SIAKAD/PDDikti)
 * that owns the identity data shown (read-only) on the tracer form — NIM,
 * Kode PT, Kode Prodi, Tahun Lulus, Nama Mahasiswa, NIK, etc. Swap the bound
 * implementation (see AppServiceProvider) for a real API client without
 * touching TracerResponseController.
 */
interface AcademicInfoServiceContract
{
    public function forAlumni(Alumni $alumni): AcademicIdentity;
}
