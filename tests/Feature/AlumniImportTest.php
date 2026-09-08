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

    public function test_faculty_is_guessed_from_the_nims_first_letter_when_no_faculty_info_is_given(): void
    {
        Faculty::factory()->create(['code' => 'H', 'name' => 'Teknik']);

        $file = $this->csvFile(
            ['nim', 'nama', 'tahunlulus', 'kodeprog', 'namajenjang', 'namaprogdikti'],
            [[
                'nim' => 'H1D019057', // starts with "H" -> matches the existing "Teknik" faculty
                'nama' => 'Contoh Tebakan NIM',
                'tahunlulus' => 2025,
                'kodeprog' => '55201',
                'namajenjang' => 'S1',
                'namaprogdikti' => 'Informatika',
            ]]
        );

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('alumni.import'), ['file' => $file]);

        $response->assertRedirect(route('alumni.import.form'));
        $this->assertDatabaseHas('program_studies', ['code' => '55201', 'name' => 'Informatika']);
        $this->assertDatabaseHas('alumni', [
            'nim' => 'H1D019057',
            'faculty_id' => Faculty::where('code', 'H')->value('id'),
        ]);
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

    public function test_duplicate_login_email_falls_back_to_a_nim_based_email_without_failing_the_import(): void
    {
        $faculty = Faculty::factory()->create(['code' => 'H']);
        $studyProgram = StudyProgram::factory()->create(['code' => '55201', 'faculty_id' => $faculty->id]);

        // An existing user already owns this email under a different NIM —
        // e.g. the same person continuing from S1 to S2 with a new NIM.
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

        // Both alumni rows are saved...
        $this->assertDatabaseHas('alumni', ['nim' => 'A0A021010', 'nama' => 'Punya Email Bentrok']);
        $this->assertDatabaseHas('alumni', ['nim' => 'A0A021011']);

        // ...and both get a login account — the colliding one falls back to a NIM-based
        // email (login is by NIM + Tanggal Lahir, not by email) instead of being skipped.
        $this->assertDatabaseHas('users', ['nim' => 'A0A021010', 'email' => 'A0A021010@mhs.unsoed.ac.id']);
        $this->assertDatabaseHas('users', ['nim' => 'A0A021011', 'email' => 'normal@example.com']);

        $response->assertSessionHas('importSkipped', function (array $skipped) {
            return count($skipped) === 1 && str_contains($skipped[0]['reason'], 'A0A021010') && str_contains($skipped[0]['reason'], 'shared@example.com');
        });
    }

    public function test_new_faculty_without_a_name_column_gets_the_known_unsoed_name_instead_of_its_bare_code(): void
    {
        // The real national export format has no nama_fakultas column at all
        // — only kode_fakultas — so the fallback must resolve "A" to
        // "Pertanian" instead of saving the faculty as literally named "A".
        $file = $this->csvFile(
            ['nim', 'nama', 'tahunlulus', 'kodeprog', 'namajenjang', 'namaprogdikti', 'kode_fakultas'],
            [[
                'nim' => 'A0A021009',
                'nama' => 'Contoh Tanpa Nama Fakultas',
                'tahunlulus' => 2025,
                'kodeprog' => '54403',
                'namajenjang' => 'D3',
                'namaprogdikti' => 'Agribisnis',
                'kode_fakultas' => 'A',
            ]]
        );

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)->post(route('alumni.import'), ['file' => $file]);

        $this->assertDatabaseHas('faculties', ['code' => 'A', 'name' => 'Pertanian']);
    }

    public function test_kode_fakultas_with_stray_whitespace_reuses_the_same_faculty_instead_of_duplicating_it(): void
    {
        // Real exports sometimes carry invisible formatting noise (a
        // trailing space, a non-breaking space) in kode_fakultas — without
        // normalizing it, each row would create its own "duplicate" faculty
        // that only looks the same when rendered.
        $file = $this->csvFile(
            ['nim', 'nama', 'tahunlulus', 'kodeprog', 'namajenjang', 'namaprogdikti', 'kode_fakultas'],
            [
                [
                    'nim' => 'A0A021012',
                    'nama' => 'Baris Satu',
                    'tahunlulus' => 2025,
                    'kodeprog' => '54404',
                    'namajenjang' => 'D3',
                    'namaprogdikti' => 'Agribisnis',
                    'kode_fakultas' => 'A',
                ],
                [
                    'nim' => 'A0A021013',
                    'nama' => 'Baris Dua',
                    'tahunlulus' => 2025,
                    'kodeprog' => '54405',
                    'namajenjang' => 'D3',
                    'namaprogdikti' => 'Agroteknologi',
                    'kode_fakultas' => 'A ', // trailing space
                ],
            ]
        );

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)->post(route('alumni.import'), ['file' => $file]);

        $this->assertSame(1, Faculty::where('code', 'A')->count());
        $this->assertDatabaseHas('faculties', ['code' => 'A', 'name' => 'Pertanian']);
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

    // Admin Fakultas is no longer able to import alumni data at all (see
    // ImportAccessTest::test_only_super_admin_and_admin_universitas_can_import_tracer_or_alumni_data)
    // — this used to test that they were at least confined to their own
    // faculty, which is moot now that they can't reach this endpoint.
}
