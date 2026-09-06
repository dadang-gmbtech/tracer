<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\User;
use App\Support\TracerFieldCodes;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TracerImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function csvFile(array $rows): UploadedFile
    {
        $headers = TracerFieldCodes::exportColumns();
        $lines = [implode(',', $headers)];

        foreach ($rows as $overrides) {
            $row = array_fill_keys($headers, '');
            foreach ($overrides as $key => $value) {
                $row[$key] = $value;
            }
            $lines[] = implode(',', array_map(fn ($h) => $row[$h], $headers));
        }

        return UploadedFile::fake()->createWithContent('tracer.csv', implode("\n", $lines));
    }

    public function test_the_template_download_is_a_lightweight_file_not_the_whole_dataset(): void
    {
        // Regression: the import page used to link to the full data export
        // as its "template" — confusing (it isn't one) and, for a
        // university with thousands of alumni, unnecessarily heavy.
        Alumni::factory()->count(3)->create();

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->get(route('tracer.import.template'));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=template-data-tracer.xlsx');
    }

    public function test_admin_universitas_can_bulk_update_tracer_answers(): void
    {
        $alumni = Alumni::factory()->create(['nim' => 'A1A100AAA']);

        $file = $this->csvFile([[
            'nimhsmsmh' => $alumni->nim,
            'f8' => 1,
            'f502' => 2,
            'f505' => 4500000,
            'f1201' => 1,
            'f14' => 1,
            'f15' => 2,
        ]]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('tracer.import'), ['file' => $file]);

        $response->assertRedirect(route('tracer.import.form'));
        $this->assertDatabaseHas('tracer_responses', [
            'alumni_id' => $alumni->id,
            'f8' => 1,
            'f502' => 2,
        ]);
    }

    public function test_f504_column_is_not_part_of_the_export_or_import_columns(): void
    {
        $this->assertNotContains('f504', TracerFieldCodes::exportColumns());
        $this->assertNotContains('f504', TracerFieldCodes::codes());
    }

    public function test_unknown_nim_without_faculty_info_is_skipped_and_reported(): void
    {
        $file = $this->csvFile([['nimhsmsmh' => 'TIDAKADA123', 'f8' => 1]]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('tracer.import'), ['file' => $file]);

        $response->assertRedirect(route('tracer.import.form'));
        $response->assertSessionHas('importSkipped', function (array $skipped) {
            return count($skipped) === 1 && str_contains($skipped[0]['reason'], 'TIDAKADA123');
        });
        $this->assertDatabaseMissing('alumni', ['nim' => 'TIDAKADA123']);
    }

    public function test_reported_row_numbers_account_for_the_heading_row_and_chunked_reading(): void
    {
        // Row numbers come from the chunk-reading offset (see
        // TracerResponsesImport::chunkSize()), not a plain array index —
        // this guards against that offset math being wrong.
        $file = $this->csvFile([
            ['nimhsmsmh' => 'A1A100AAA', 'f8' => 1],
            ['nimhsmsmh' => 'TIDAKADA999', 'f8' => 1],
        ]);
        Alumni::factory()->create(['nim' => 'A1A100AAA']);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('tracer.import'), ['file' => $file]);

        $response->assertSessionHas('importSkipped', function (array $skipped) {
            return count($skipped) === 1 && $skipped[0]['row'] === 3;
        });
    }

    public function test_admin_universitas_can_bootstrap_a_new_alumni_and_its_faculty_from_a_full_row(): void
    {
        $file = $this->csvFile([[
            'nimhsmsmh' => 'A1A300CCC',
            'nmmhsmsmh' => 'Contoh Alumni Baru',
            'emailmsmh' => 'contoh@example.com',
            'tahun_lulus' => 2024,
            'kodefak' => 'H',
            'namafakultas' => 'Teknik',
            'kodeprog' => '55201',
            'namajenjang' => 'S1',
            'namaprogdikti' => 'Informatika',
            'f8' => 1,
        ]]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('tracer.import'), ['file' => $file]);

        $response->assertRedirect(route('tracer.import.form'));
        $this->assertDatabaseHas('alumni', ['nim' => 'A1A300CCC', 'nama' => 'Contoh Alumni Baru']);
        $this->assertDatabaseHas('faculties', ['code' => 'H', 'name' => 'Teknik']);
        $this->assertDatabaseHas('program_studies', ['code' => '55201', 'name' => 'Informatika']);
        $this->assertDatabaseHas('tracer_responses', ['f8' => 1]);

        // No real tanggal_lahir is available from this format, so no login account is created.
        $this->assertDatabaseMissing('users', ['nim' => 'A1A300CCC']);
    }

    public function test_faculty_is_guessed_from_the_nims_first_letter_when_kodefak_is_missing(): void
    {
        Faculty::factory()->create(['code' => 'H', 'name' => 'Teknik']);

        $file = $this->csvFile([[
            'nimhsmsmh' => 'H1D019099', // starts with "H" -> matches the existing "Teknik" faculty
            'nmmhsmsmh' => 'Contoh Tebakan NIM',
            'tahun_lulus' => 2024,
            // kodefak/namafakultas intentionally left blank
            'kodeprog' => '55201',
            'namajenjang' => 'S1',
            'namaprogdikti' => 'Informatika',
            'f8' => 1,
        ]]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)->post(route('tracer.import'), ['file' => $file]);

        $this->assertDatabaseHas('alumni', [
            'nim' => 'H1D019099',
            'faculty_id' => Faculty::where('code', 'H')->value('id'),
        ]);
        $this->assertDatabaseHas('program_studies', ['code' => '55201', 'name' => 'Informatika']);
    }

    public function test_an_implausible_kodefak_value_is_ignored_in_favor_of_guessing_from_the_nim(): void
    {
        // A misaligned column in a real export once put a student's email
        // address in kodefak — trusting it at face value created a garbage
        // faculty (see FacultyCodeGuesser::normalize()). It should be
        // rejected and fall through to the NIM-based guess instead.
        Faculty::factory()->create(['code' => 'H', 'name' => 'Teknik']);

        $file = $this->csvFile([[
            'nimhsmsmh' => 'H1D019099',
            'nmmhsmsmh' => 'Contoh Kodefak Rusak',
            'tahun_lulus' => 2024,
            'kodefak' => 'someone@mhs.unsoed.ac.id',
            'kodeprog' => '55201',
            'namajenjang' => 'S1',
            'namaprogdikti' => 'Informatika',
            'f8' => 1,
        ]]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)->post(route('tracer.import'), ['file' => $file]);

        $this->assertDatabaseHas('alumni', [
            'nim' => 'H1D019099',
            'faculty_id' => Faculty::where('code', 'H')->value('id'),
        ]);
        $this->assertDatabaseMissing('faculties', ['code' => 'someone@mhs.unsoed.ac.id']);
    }

    public function test_admin_fakultas_cannot_import_tracer_data_for_alumni_outside_their_faculty(): void
    {
        $ownFaculty = Faculty::factory()->create();
        $otherFaculty = Faculty::factory()->create();
        $alumni = Alumni::factory()->create(['faculty_id' => $otherFaculty->id, 'nim' => 'A1A200BBB']);

        $file = $this->csvFile([['nimhsmsmh' => $alumni->nim, 'f8' => 1]]);

        $admin = User::factory()->create(['faculty_id' => $ownFaculty->id]);
        $admin->assignRole('Admin Fakultas');

        $this->actingAs($admin)->post(route('tracer.import'), ['file' => $file]);

        $this->assertDatabaseMissing('tracer_responses', ['alumni_id' => $alumni->id]);
    }

    public function test_admin_fakultas_cannot_bootstrap_a_new_alumni_for_another_faculty(): void
    {
        $ownFaculty = Faculty::factory()->create(['code' => 'H']);

        $file = $this->csvFile([[
            'nimhsmsmh' => 'A1A400DDD',
            'kodefak' => 'X', // not the admin's own faculty code
            'namafakultas' => 'Fakultas Lain',
            'kodeprog' => '99999',
            'namaprogdikti' => 'Prodi Lain',
            'f8' => 1,
        ]]);

        $admin = User::factory()->create(['faculty_id' => $ownFaculty->id]);
        $admin->assignRole('Admin Fakultas');

        $response = $this->actingAs($admin)->post(route('tracer.import'), ['file' => $file]);

        $response->assertSessionHas('importSkipped', function (array $skipped) {
            return count($skipped) === 1 && str_contains($skipped[0]['reason'], 'A1A400DDD');
        });
        $this->assertDatabaseMissing('alumni', ['nim' => 'A1A400DDD']);
    }
}
