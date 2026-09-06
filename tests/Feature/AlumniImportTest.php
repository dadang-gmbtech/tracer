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
            ['nim', 'nama', 'tahunlulu', 'emailunsoed', 'emailpersonal', 'notelp', 'tgllahir', 'kodeprog'],
            [[
                'nim' => 'A0A021001',
                'nama' => 'Nurhanif',
                'tahunlulu' => 2025,
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
            ['nim', 'nama', 'tahunlulu', 'kodeprog', 'namajenjang', 'namaprogdikti', 'kode_fakultas', 'nama_fakultas'],
            [[
                'nim' => 'A0A021003',
                'nama' => 'Contoh Dua',
                'tahunlulu' => 2025,
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
            ['nim', 'nama', 'tahunlulu', 'kodeprog', 'tgllahir', 'emailpersonal'],
            [[
                'nim' => 'A0A021004',
                'nama' => 'Fifi Febiola',
                'tahunlulu' => 2025,
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
