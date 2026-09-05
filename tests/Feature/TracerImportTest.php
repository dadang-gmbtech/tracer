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

    public function test_row_with_unknown_nim_is_skipped_and_reported(): void
    {
        $file = $this->csvFile([['nimhsmsmh' => 'TIDAKADA123', 'f8' => 1]]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('tracer.import'), ['file' => $file]);

        $response->assertRedirect(route('tracer.import.form'));
        $response->assertSessionHas('importSkipped', function (array $skipped) {
            return count($skipped) === 1 && str_contains($skipped[0]['reason'], 'TIDAKADA123');
        });
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
}
