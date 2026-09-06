<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\EmployerResponse;
use App\Models\Faculty;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EmployerImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function csvFile(array $rows): UploadedFile
    {
        $headers = [
            'nim', 'nama_pengisi', 'jabatan', 'nama_perusahaan', 'alamat_perusahaan', 'no_telp',
            'q1_kerja_sama_tim', 'q2_pengembangan_diri', 'q3_komunikasi', 'q4_teknologi_informasi',
            'q5_bahasa_asing', 'q6_keahlian', 'q7_integritas',
        ];
        $lines = [implode(',', $headers)];

        foreach ($rows as $overrides) {
            $row = array_fill_keys($headers, '');
            foreach ($overrides as $key => $value) {
                $row[$key] = $value;
            }
            $lines[] = implode(',', array_map(fn ($h) => $row[$h], $headers));
        }

        return UploadedFile::fake()->createWithContent('pengguna-alumni.csv', implode("\n", $lines));
    }

    public function test_the_import_form_renders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)
            ->get(route('employer.import.form'))
            ->assertOk()
            ->assertSee('Impor Data Pengguna Alumni');
    }

    public function test_admin_universitas_can_bulk_add_employer_feedback(): void
    {
        $alumni = Alumni::factory()->create(['nim' => 'A1A100AAA']);

        $file = $this->csvFile([[
            'nim' => $alumni->nim,
            'nama_pengisi' => 'Budi Santoso',
            'jabatan' => 'HRD Manager',
            'nama_perusahaan' => 'PT Contoh Sejahtera',
            'q1_kerja_sama_tim' => 1,
            'q2_pengembangan_diri' => 2,
            'q3_komunikasi' => 1,
            'q4_teknologi_informasi' => 2,
            'q5_bahasa_asing' => 3,
            'q6_keahlian' => 1,
            'q7_integritas' => 1,
        ]]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('employer.import'), ['file' => $file]);

        $response->assertRedirect(route('employer.import.form'));
        $this->assertDatabaseHas('employer_responses', [
            'alumni_id' => $alumni->id,
            'nama_pengisi' => 'Budi Santoso',
            'nama_perusahaan' => 'PT Contoh Sejahtera',
            'q1_kerja_sama_tim' => 1,
        ]);
    }

    public function test_an_alumni_can_have_more_than_one_employer_response(): void
    {
        $alumni = Alumni::factory()->create(['nim' => 'A1A100BBB']);

        $file = $this->csvFile([
            ['nim' => $alumni->nim, 'nama_pengisi' => 'Penilai Satu', 'nama_perusahaan' => 'PT Satu', 'q1_kerja_sama_tim' => 1, 'q2_pengembangan_diri' => 1, 'q3_komunikasi' => 1, 'q4_teknologi_informasi' => 1, 'q5_bahasa_asing' => 1, 'q6_keahlian' => 1, 'q7_integritas' => 1],
            ['nim' => $alumni->nim, 'nama_pengisi' => 'Penilai Dua', 'nama_perusahaan' => 'PT Dua', 'q1_kerja_sama_tim' => 2, 'q2_pengembangan_diri' => 2, 'q3_komunikasi' => 2, 'q4_teknologi_informasi' => 2, 'q5_bahasa_asing' => 2, 'q6_keahlian' => 2, 'q7_integritas' => 2],
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)->post(route('employer.import'), ['file' => $file]);

        $this->assertSame(2, EmployerResponse::where('alumni_id', $alumni->id)->count());
    }

    public function test_unknown_nim_is_skipped_and_reported(): void
    {
        $file = $this->csvFile([[
            'nim' => 'TIDAKADA123',
            'nama_pengisi' => 'Contoh',
            'nama_perusahaan' => 'PT Contoh',
            'q1_kerja_sama_tim' => 1, 'q2_pengembangan_diri' => 1, 'q3_komunikasi' => 1,
            'q4_teknologi_informasi' => 1, 'q5_bahasa_asing' => 1, 'q6_keahlian' => 1, 'q7_integritas' => 1,
        ]]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('employer.import'), ['file' => $file]);

        $response->assertSessionHas('importSkipped', function (array $skipped) {
            return count($skipped) === 1 && str_contains($skipped[0]['reason'], 'TIDAKADA123');
        });
        $this->assertDatabaseMissing('employer_responses', []);
    }

    public function test_a_rating_outside_1_to_4_is_skipped_and_reported(): void
    {
        $alumni = Alumni::factory()->create(['nim' => 'A1A100CCC']);

        $file = $this->csvFile([[
            'nim' => $alumni->nim,
            'nama_pengisi' => 'Contoh',
            'nama_perusahaan' => 'PT Contoh',
            'q1_kerja_sama_tim' => 9,
            'q2_pengembangan_diri' => 1, 'q3_komunikasi' => 1, 'q4_teknologi_informasi' => 1,
            'q5_bahasa_asing' => 1, 'q6_keahlian' => 1, 'q7_integritas' => 1,
        ]]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('employer.import'), ['file' => $file]);

        $response->assertSessionHas('importSkipped', function (array $skipped) {
            return count($skipped) === 1 && str_contains($skipped[0]['reason'], 'q1_kerja_sama_tim');
        });
        $this->assertDatabaseMissing('employer_responses', ['alumni_id' => $alumni->id]);
    }

    public function test_admin_fakultas_cannot_add_feedback_for_an_alumni_outside_their_faculty(): void
    {
        $ownFaculty = Faculty::factory()->create();
        $otherFaculty = Faculty::factory()->create();
        $alumni = Alumni::factory()->create(['faculty_id' => $otherFaculty->id, 'nim' => 'A1A100DDD']);

        $file = $this->csvFile([[
            'nim' => $alumni->nim,
            'nama_pengisi' => 'Contoh',
            'nama_perusahaan' => 'PT Contoh',
            'q1_kerja_sama_tim' => 1, 'q2_pengembangan_diri' => 1, 'q3_komunikasi' => 1,
            'q4_teknologi_informasi' => 1, 'q5_bahasa_asing' => 1, 'q6_keahlian' => 1, 'q7_integritas' => 1,
        ]]);

        $admin = User::factory()->create(['faculty_id' => $ownFaculty->id]);
        $admin->assignRole('Admin Fakultas');

        $this->actingAs($admin)->post(route('employer.import'), ['file' => $file]);

        $this->assertDatabaseMissing('employer_responses', ['alumni_id' => $alumni->id]);
    }
}
