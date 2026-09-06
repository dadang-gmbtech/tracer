<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_universitas_can_export_tracer_data_to_excel(): void
    {
        Alumni::factory()->count(3)->create();

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->get(route('export.tracer'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_the_tracer_export_form_renders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)
            ->get(route('export.tracer.form'))
            ->assertOk()
            ->assertSee('Tahun Lulus Dari');
    }

    public function test_tracer_export_can_be_limited_to_a_graduation_year_range(): void
    {
        Alumni::factory()->create(['graduation_year' => 2022]);
        $inRange = Alumni::factory()->create(['graduation_year' => 2023]);
        Alumni::factory()->create(['graduation_year' => 2025]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->get(route('export.tracer', [
            'graduation_year_from' => 2023,
            'graduation_year_to' => 2024,
        ]));

        $response->assertOk();

        $rows = Excel::toCollection(null, $response->getFile()->getPathname())->first();

        // Header row + exactly the one alumni inside the 2023-2024 range.
        $this->assertCount(2, $rows);
        $this->assertSame($inRange->nim, $rows[1][2]);
    }

    public function test_tracer_export_can_be_limited_to_selected_jenjang(): void
    {
        $s1 = StudyProgram::factory()->create(['level' => 'S1']);
        $s2 = StudyProgram::factory()->create(['level' => 'S2']);
        $d3Alumni = Alumni::factory()->create(['program_study_id' => StudyProgram::factory()->create(['level' => 'D3'])->id]);
        $s1Alumni = Alumni::factory()->create(['program_study_id' => $s1->id]);
        Alumni::factory()->create(['program_study_id' => $s2->id]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->get(route('export.tracer', [
            'jenjang' => ['D3', 'S1'],
        ]));

        $response->assertOk();

        $rows = Excel::toCollection(null, $response->getFile()->getPathname())->first();
        $exportedNims = $rows->slice(1)->pluck(2)->all();

        $this->assertCount(3, $rows); // header + 2 matching alumni
        $this->assertContains($d3Alumni->nim, $exportedNims);
        $this->assertContains($s1Alumni->nim, $exportedNims);
    }

    public function test_surveyor_without_export_permission_is_forbidden(): void
    {
        $surveyor = User::factory()->create();
        $surveyor->assignRole('Surveyor');

        $this->actingAs($surveyor)->get(route('export.tracer'))->assertForbidden();
    }
}
