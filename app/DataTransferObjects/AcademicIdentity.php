<?php

namespace App\DataTransferObjects;

/**
 * Read-only identity block shown at the top of the tracer form (see
 * "Identitas" in Form Tracer Studi.pdf): NIM, Kode PT, Tahun Lulus, Kode
 * Prodi, Nama Mahasiswa, Nomor Telepon/HP, Alamat Email, NIK, NPWP.
 */
readonly class AcademicIdentity
{
    public function __construct(
        public string $nim,
        public string $namaMahasiswa,
        public string $kodePt,
        public ?string $kodeProdi,
        public ?int $tahunLulus,
        public ?string $noTelp,
        public ?string $email,
        public ?string $nik,
        public ?string $npwp,
    ) {}
}
