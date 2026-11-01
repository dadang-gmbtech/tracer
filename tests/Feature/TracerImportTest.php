<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\TracerResponse;
use App\Models\User;
use App\Support\TracerFieldCodes;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class TracerImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function csvFile(array $rows, ?array $headers = null): UploadedFile
    {
        // Built with fputcsv rather than a plain implode(','): one of the
        // real headers ("Tahun Lulus\n Keluar") contains an embedded
        // newline, which a naive comma-join would split into a stray extra
        // line instead of keeping it quoted inside a single CSV field.
        $headers ??= TracerFieldCodes::exportColumns();
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, $headers);

        foreach ($rows as $overrides) {
            $row = array_fill_keys($headers, '');
            foreach ($overrides as $key => $value) {
                $row[$key] = $value;
            }
            fputcsv($stream, array_map(fn ($h) => $row[$h], $headers));
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return UploadedFile::fake()->createWithContent('tracer.csv', $content);
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
            'NIM/Nomor Mhs' => $alumni->nim,
            'F8' => 1,
            'F502' => 2,
            'F505' => 4500000,
            'F1201' => 1,
            'F14' => 1,
            'F15' => 2,
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

    public function test_waktu_update_sets_the_submission_date_used_by_the_monthly_recap(): void
    {
        $alumni = Alumni::factory()->create(['nim' => 'A1A100AAA']);

        $file = $this->csvFile([[
            'NIM/Nomor Mhs' => $alumni->nim,
            'F8' => 1,
            'waktu_update' => '15/08/2025',
        ]], TracerFieldCodes::importColumns());

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)->post(route('tracer.import'), ['file' => $file]);

        $this->assertSame('2025-08-15', $alumni->tracerResponse()->first()->submitted_at->toDateString());
    }

    public function test_reimporting_without_waktu_update_keeps_the_existing_submission_date(): void
    {
        $alumni = Alumni::factory()->create(['nim' => 'A1A100AAA']);
        TracerResponse::factory()->create(['alumni_id' => $alumni->id, 'f8' => 1, 'submitted_at' => '2025-03-10 09:00:00']);

        $file = $this->csvFile([['NIM/Nomor Mhs' => $alumni->nim, 'F8' => 3]]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)->post(route('tracer.import'), ['file' => $file]);

        $response = $alumni->tracerResponse()->first();
        $this->assertSame(3, (int) $response->f8);
        $this->assertSame('2025-03-10', $response->submitted_at->toDateString());
    }

    public function test_cells_holding_formulas_linked_to_another_workbook_import_their_saved_values(): void
    {
        // A file whose NIM column is "=[1]Data!C2" (pulled from another
        // workbook that isn't uploaded) used to import the formula text
        // itself as the NIM. Excel saves each formula's last result in the
        // file, and that is what should be read.
        $alumni = Alumni::factory()->create(['nim' => 'A1A100AAA']);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([TracerFieldCodes::importColumns()]);
        $nimColumn = array_search('NIM/Nomor Mhs', TracerFieldCodes::importColumns(), true) + 1;
        $f8Column = array_search('F8', TracerFieldCodes::importColumns(), true) + 1;
        $nimCell = Coordinate::stringFromColumnIndex($nimColumn).'2';
        $sheet->setCellValue($nimCell, '=[1]Data!C2');
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($f8Column).'2', 1);

        $path = tempnam(sys_get_temp_dir(), 'tracer').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($path);

        // PhpSpreadsheet can't produce Excel's saved result for an external
        // link, so add it to the sheet XML the way Excel writes it.
        $zip = new \ZipArchive;
        $zip->open($path);
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $xml = preg_replace(
            '#<c r="'.$nimCell.'"([^>]*)><f>\[1\]Data!C2</f></c>#',
            '<c r="'.$nimCell.'"$1 t="str"><f>[1]Data!C2</f><v>A1A100AAA</v></c>',
            $xml,
        );
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
        $zip->close();

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('tracer.import'), [
            'file' => new UploadedFile($path, 'tracer.xlsx', null, null, true),
        ]);

        $response->assertSessionHas('importSkipped', []);
        $this->assertDatabaseHas('tracer_responses', ['alumni_id' => $alumni->id, 'f8' => 1]);
    }

    public function test_the_import_template_ends_with_a_waktu_update_column(): void
    {
        $columns = TracerFieldCodes::importColumns();

        $this->assertSame('waktu_update', end($columns));
        $this->assertSame(TracerFieldCodes::exportColumns(), array_slice($columns, 0, -1));
    }

    public function test_f504_and_f506_columns_are_not_part_of_the_export_or_import_columns(): void
    {
        $this->assertNotContains('f504', TracerFieldCodes::codes());
        $this->assertNotContains('f506', TracerFieldCodes::codes());
        $this->assertNotContains('F504', TracerFieldCodes::exportColumns());
        $this->assertNotContains('F506', TracerFieldCodes::exportColumns());
    }

    public function test_unknown_nim_without_a_resolvable_prodi_code_is_skipped_and_reported(): void
    {
        $file = $this->csvFile([['NIM/Nomor Mhs' => 'TIDAKADA123', 'F8' => 1]]);

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
            ['NIM/Nomor Mhs' => 'A1A100AAA', 'F8' => 1],
            ['NIM/Nomor Mhs' => 'TIDAKADA999', 'F8' => 1],
        ]);
        Alumni::factory()->create(['nim' => 'A1A100AAA']);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('tracer.import'), ['file' => $file]);

        $response->assertSessionHas('importSkipped', function (array $skipped) {
            return count($skipped) === 1 && $skipped[0]['row'] === 3;
        });
    }

    public function test_admin_universitas_can_bootstrap_a_new_alumni_from_an_existing_study_program_code(): void
    {
        // This format only carries a prodi CODE (no name/level/faculty
        // columns), so bootstrapping a new alumni relies entirely on that
        // code already being registered as master data.
        $faculty = Faculty::factory()->create(['code' => 'H', 'name' => 'Teknik']);
        $studyProgram = StudyProgram::factory()->create([
            'code' => '55201',
            'name' => 'Informatika',
            'level' => 'S1',
            'faculty_id' => $faculty->id,
        ]);

        $file = $this->csvFile([[
            'NIM/Nomor Mhs' => 'A1A300CCC',
            'Nama Mhs' => 'Contoh Alumni Baru',
            'Email Mhs' => 'contoh@example.com',
            "Tahun Lulus\n Keluar" => 2024,
            'Kode Prodi' => $studyProgram->code,
            'F8' => 1,
        ]]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('tracer.import'), ['file' => $file]);

        $response->assertRedirect(route('tracer.import.form'));
        $this->assertDatabaseHas('alumni', [
            'nim' => 'A1A300CCC',
            'nama' => 'Contoh Alumni Baru',
            'faculty_id' => $faculty->id,
            'program_study_id' => $studyProgram->id,
        ]);
        $this->assertDatabaseHas('tracer_responses', ['f8' => 1]);

        // No real tanggal_lahir is available from this format, so no login account is created.
        $this->assertDatabaseMissing('users', ['nim' => 'A1A300CCC']);
    }

    public function test_a_prodi_code_that_does_not_exist_cannot_bootstrap_a_new_alumni(): void
    {
        $file = $this->csvFile([[
            'NIM/Nomor Mhs' => 'A1A300CCC',
            'Nama Mhs' => 'Contoh Alumni Baru',
            'Kode Prodi' => '99999',
            'F8' => 1,
        ]]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->post(route('tracer.import'), ['file' => $file]);

        $response->assertSessionHas('importSkipped', function (array $skipped) {
            return count($skipped) === 1 && str_contains($skipped[0]['reason'], 'kode prodi tidak ditemukan');
        });
        $this->assertDatabaseMissing('alumni', ['nim' => 'A1A300CCC']);
    }

    // Admin Fakultas is no longer able to import tracer data at all (see
    // ImportAccessTest::test_only_super_admin_and_admin_universitas_can_import_tracer_or_alumni_data)
    // — this used to test that they were at least confined to their own
    // faculty, which is moot now that they can't reach this endpoint.
}
