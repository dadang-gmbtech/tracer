<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AlumniImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function csvFile(array $headers, array $rows): UploadedFile
    {
        $lines = [implode(',', $headers)];

        foreach ($rows as $overrides) {
            $row = array_fill_keys($headers, '');
            foreach ($overrides as $key => $value) {
                $row[$key] = $value;
            }
            $lines[] = implode(',', array_map(fn ($h) => $row[$h], $headers));
        }

        return UploadedFile::fake()->createWithContent('alumni.csv', implode("\n", $lines));
    }

    public function test_admin_universitas_can_create_alumni_matching_an_existing_program_studi(): void
    {
        $faculty = Faculty::factory()->create(['code' => 'H']);
        $studyProgram = StudyProgram::factory()->create(['code' => '55201', 'faculty_id' => $faculty->id]);

        $file = $this->csvFile(
            ['nim', 'nama', 'tahunlulus', 'emailunsoed', 'emailpersonal', 'notelp', 'tgllahir', 'kodeprog'],
            [[
                'nim' => 'A0A021001',
                'nama' => 'Nurhanif',
                'tahunlulus' => 2025,
                'emailunsoed' => 'nurhanif@mhs.unsoed.ac.id',
                'emailpersonal' => 'hanifnur@gmail.com',
                'notelp' => '085877270746',
                'tgllahir' => '',
                'kodeprog' => $studyProgram->code,
            ]]
        );

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('alumni.import'), ['file' => $file]);

        $response->assertRedirect(route('alumni.import.form'));
        $this->assertDatabaseHas('alumni', [
            'nim' => 'A0A021001',
            'nama' => 'Nurhanif',
            'email' => 'hanifnur@gmail.com', // emailpersonal preferred over emailunsoed
            'program_study_id' => $studyProgram->id,
            'faculty_id' => $faculty->id,
        ]);
        $this->assertDatabaseMissing('users', ['nim' => 'A0A021001']);
    }

    public function test_row_with_unregistered_prodi_and_no_faculty_columns_is_skipped(): void
    {
        $file = $this->csvFile(
            ['nim', 'nama', 'kodeprog'],
            [['nim' => 'A0A021002', 'nama' => 'Contoh', 'kodeprog' => '99999']]
        );

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('alumni.import'), ['file' => $file]);

        $response->assertSessionHas('importSkipped', function (array $skipped) {
            return count($skipped) === 1 && str_contains($skipped[0]['reason'], 'A0A021002');
        });
        $this->assertDatabaseMissing('alumni', ['nim' => 'A0A021002']);
    }

    public function test_admin_universitas_can_bootstrap_a_new_program_studi_when_faculty_columns_are_present(): void
    {
        $file = $this->csvFile(
            ['nim', 'nama', 'tahunlulus', 'kodeprog', 'namajenjang', 'namaprogdikti', 'kode_fakultas', 'nama_fakultas'],
            [[
                'nim' => 'A0A021003',
                'nama' => 'Contoh Dua',
                'tahunlulus' => 2025,
                'kodeprog' => '54401',
                'namajenjang' => 'D3',
                'namaprogdikti' => 'Agribisnis',
                'kode_fakultas' => 'A',
                'nama_fakultas' => 'Pertanian',
            ]]
        );

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)->post(route('alumni.import'), ['file' => $file]);

        $this->assertDatabaseHas('faculties', ['code' => 'A', 'name' => 'Pertanian']);
        $this->assertDatabaseHas('program_studies', ['code' => '54401', 'name' => 'Agribisnis', 'level' => 'D3']);
        $this->assertDatabaseHas('alumni', ['nim' => 'A0A021003']);
    }

    public function test_filled_birth_date_provisions_a_login_account(): void
    {
        $faculty = Faculty::factory()->create(['code' => 'H']);
        $studyProgram = StudyProgram::factory()->create(['code' => '55201', 'faculty_id' => $faculty->id]);

        $file = $this->csvFile(
            ['nim', 'nama', 'tahunlulus', 'kodeprog', 'tgllahir', 'emailpersonal'],
            [[
                'nim' => 'A0A021004',
                'nama' => 'Fifi Febiola',
                'tahunlulus' => 2025,
                'kodeprog' => $studyProgram->code,
                'tgllahir' => '2003-02-13',
                'emailpersonal' => 'fifi@example.com',
            ]]
        );

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)->post(route('alumni.import'), ['file' => $file]);

        $this->assertDatabaseHas('users', ['nim' => 'A0A021004', 'email' => 'fifi@example.com']);
        $user = User::where('nim', 'A0A021004')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Alumni'));
        $this->assertSame('2003-02-13', $user->tanggal_lahir->format('Y-m-d'));
    }

    public function test_duplicate_login_email_skips_only_that_login_without_failing_the_whole_import(): void
    {
        $faculty = Faculty::factory()->create(['code' => 'H']);
        $studyProgram = StudyProgram::factory()->create(['code' => '55201', 'faculty_id' => $faculty->id]);

        // An existing user already owns this email under a different NIM.
        User::factory()->create(['nim' => 'A0A020999', 'email' => 'shared@example.com']);

        $file = $this->csvFile(
            ['nim', 'nama', 'tahunlulus', 'kodeprog', 'tgllahir', 'emailpersonal'],
            [
                [
                    'nim' => 'A0A021010',
                    'nama' => 'Punya Email Bentrok',
                    'tahunlulus' => 2025,
                    'kodeprog' => $studyProgram->code,
                    'tgllahir' => '2003-02-13',
                    'emailpersonal' => 'shared@example.com',
                ],
                [
                    'nim' => 'A0A021011',
                    'nama' => 'Baris Normal Setelahnya',
                    'tahunlulus' => 2025,
                    'kodeprog' => $studyProgram->code,
                    'tgllahir' => '2004-01-01',
                    'emailpersonal' => 'normal@example.com',
                ],
            ]
        );

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('alumni.import'), ['file' => $file]);

        // Both alumni rows are saved regardless of the login-account collision...
        $this->assertDatabaseHas('alumni', ['nim' => 'A0A021010', 'nama' => 'Punya Email Bentrok']);
        $this->assertDatabaseHas('alumni', ['nim' => 'A0A021011']);

        // ...but only the second one got a login account; the first is reported, not silently lost.
        $this->assertDatabaseMissing('users', ['nim' => 'A0A021010']);
        $this->assertDatabaseHas('users', ['nim' => 'A0A021011', 'email' => 'normal@example.com']);

        $response->assertSessionHas('importSkipped', function (array $skipped) {
            return count($skipped) === 1 && str_contains($skipped[0]['reason'], 'A0A021010') && str_contains($skipped[0]['reason'], 'shared@example.com');
        });
    }

    public function test_admin_universitas_can_bootstrap_a_new_program_studi_using_the_picked_faculty(): void
    {
        $faculty = Faculty::factory()->create(['code' => 'A', 'name' => 'Pertanian']);

        $file = $this->csvFile(
            ['nim', 'nama', 'tahunlulus', 'kodeprog', 'namajenjang', 'namaprogdikti'],
            [[
                'nim' => 'A0A021006',
                'nama' => 'Contoh Empat',
                'tahunlulus' => 2025,
                'kodeprog' => '54401',
                'namajenjang' => 'D3',
                'namaprogdikti' => 'Agribisnis',
            ]]
        );

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('alumni.import'), [
            'file' => $file,
            'faculty_id' => $faculty->id,
        ]);

        $response->assertRedirect(route('alumni.import.form'));
        $this->assertDatabaseHas('program_studies', ['code' => '54401', 'faculty_id' => $faculty->id]);
        $this->assertDatabaseHas('alumni', ['nim' => 'A0A021006', 'faculty_id' => $faculty->id]);
    }

    public function test_admin_fakultas_cannot_pick_another_faculty_for_the_import(): void
    {
        $ownFaculty = Faculty::factory()->create(['code' => 'H']);
        $otherFaculty = Faculty::factory()->create(['code' => 'A']);

        $file = $this->csvFile(
            ['nim', 'nama', 'tahunlulus', 'kodeprog', 'namajenjang', 'namaprogdikti'],
            [[
                'nim' => 'A0A021007',
                'nama' => 'Contoh Lima',
                'tahunlulus' => 2025,
                'kodeprog' => '54402',
                'namajenjang' => 'D3',
                'namaprogdikti' => 'Peternakan',
            ]]
        );

        $admin = User::factory()->create(['faculty_id' => $ownFaculty->id]);
        $admin->assignRole('Admin Fakultas');

        // Even if a malicious/mistaken request tries to pick another faculty,
        // the server always forces the admin's own faculty_id.
        $this->actingAs($admin)->post(route('alumni.import'), [
            'file' => $file,
            'faculty_id' => $otherFaculty->id,
        ]);

        $this->assertDatabaseHas('alumni', ['nim' => 'A0A021007', 'faculty_id' => $ownFaculty->id]);
        $this->assertDatabaseMissing('alumni', ['nim' => 'A0A021007', 'faculty_id' => $otherFaculty->id]);
    }

    public function test_admin_fakultas_cannot_create_alumni_for_a_program_studi_outside_their_faculty(): void
    {
        $ownFaculty = Faculty::factory()->create(['code' => 'H']);
        $otherFaculty = Faculty::factory()->create(['code' => 'A']);
        $otherProdi = StudyProgram::factory()->create(['code' => '54401', 'faculty_id' => $otherFaculty->id]);

        $file = $this->csvFile(
            ['nim', 'nama', 'kodeprog'],
            [['nim' => 'A0A021005', 'nama' => 'Contoh Tiga', 'kodeprog' => $otherProdi->code]]
        );

        $admin = User::factory()->create(['faculty_id' => $ownFaculty->id]);
        $admin->assignRole('Admin Fakultas');

        $response = $this->actingAs($admin)->post(route('alumni.import'), ['file' => $file]);

        $response->assertSessionHas('importSkipped', function (array $skipped) {
            return count($skipped) === 1 && str_contains($skipped[0]['reason'], 'A0A021005');
        });
        $this->assertDatabaseMissing('alumni', ['nim' => 'A0A021005']);
    }
}
