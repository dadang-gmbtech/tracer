<?php

namespace App\Imports;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Template columns: nama, email, no_telp, kode_fakultas.
 * Faculty scope is forced to the importing user's own faculty when they are
 * "Admin Fakultas" (see Admin\UserController::import), otherwise taken from
 * kode_fakultas so Admin Universitas can import across faculties.
 */
class SurveyorImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    /** @var array<int, array{name: string, email: string, password: string}> */
    public array $created = [];

    public function __construct(private readonly ?int $forcedFacultyId = null) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $email = trim((string) ($row['email'] ?? ''));
            $name = trim((string) ($row['nama'] ?? ''));

            if ($email === '' || $name === '') {
                continue;
            }

            $facultyId = $this->forcedFacultyId
                ?? Faculty::where('code', trim((string) ($row['kode_fakultas'] ?? '')))->value('id');

            $password = Str::password(10, symbols: false);

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'no_telp' => $row['no_telp'] ?? null,
                    'faculty_id' => $facultyId,
                    'password' => Hash::make($password),
                    'status' => 'active',
                ]
            );

            if (! $user->hasRole('Surveyor')) {
                $user->assignRole('Surveyor');
            }

            $this->created[] = ['name' => $name, 'email' => $email, 'password' => $password];
        }
    }
}
