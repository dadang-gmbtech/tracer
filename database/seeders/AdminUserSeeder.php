<?php

namespace Database\Seeders;

use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo logins for manual testing. Password for every account below: "password".
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->makeUser('Super Admin', 'superadmin@unsoed.ac.id', 'Super Admin');
        $this->makeUser('Admin Universitas', 'admin.universitas@unsoed.ac.id', 'Admin Universitas');
        $this->makeUser('Pimpinan Universitas', 'pimpinan.universitas@unsoed.ac.id', 'Pimpinan Universitas');

        $faculty = Faculty::first();
        $studyProgram = StudyProgram::where('faculty_id', $faculty?->id)->first();

        if ($faculty) {
            $this->makeUser('Admin Fakultas', 'admin.fakultas@unsoed.ac.id', 'Admin Fakultas', [
                'faculty_id' => $faculty->id,
            ]);
            $this->makeUser('Pimpinan Fakultas', 'pimpinan.fakultas@unsoed.ac.id', 'Pimpinan Fakultas', [
                'faculty_id' => $faculty->id,
            ]);
            $this->makeUser('Surveyor', 'surveyor@unsoed.ac.id', 'Surveyor', [
                'faculty_id' => $faculty->id,
            ]);
        }

        if ($studyProgram) {
            $this->makeUser('Admin Prodi', 'admin.prodi@unsoed.ac.id', 'Admin Prodi', [
                'faculty_id' => $studyProgram->faculty_id,
                'program_study_id' => $studyProgram->id,
            ]);
        }
    }

    private function makeUser(string $name, string $email, string $role, array $extra = []): void
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => bcrypt('password'),
                'status' => 'active',
                'email_verified_at' => now(),
                ...$extra,
            ]
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
