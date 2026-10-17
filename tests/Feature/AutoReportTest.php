<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\TracerResponse;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_universitas_can_view_the_form(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)
            ->get(route('reports.auto.form'))
            ->assertOk()
            ->assertSee('Laporan Tracer Otomatis');
    }

    public function test_admin_universitas_can_download_the_generated_pdf(): void
    {
        $faculty = Faculty::factory()->create(['name' => 'Fakultas Contoh']);
        $alumni = Alumni::factory()->create(['faculty_id' => $faculty->id, 'graduation_year' => 2024]);
        TracerResponse::factory()->create(['alumni_id' => $alumni->id, 'f8' => 1]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->get(route('reports.auto.download', ['year' => 2024]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_the_pdf_still_generates_when_the_selected_year_has_no_data(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->get(route('reports.auto.download', ['year' => 2019]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_the_form_offers_a_jenjang_checklist(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $response = $this->actingAs($admin)->get(route('reports.auto.form'));

        foreach (['D3', 'S1', 'S2', 'S3'] as $level) {
            $response->assertSee('name="jenjang[]" value="'.$level.'"', false);
        }
    }

    public function test_the_report_only_counts_alumni_of_the_selected_jenjang(): void
    {
        $s1 = StudyProgram::factory()->create(['level' => 'S1']);
        $s2 = StudyProgram::factory()->create(['level' => 'S2']);
        foreach ([$s1, $s1, $s2] as $prodi) {
            $alumni = Alumni::factory()->create([
                'faculty_id' => $prodi->faculty_id, 'program_study_id' => $prodi->id, 'graduation_year' => 2025,
            ]);
            TracerResponse::factory()->create(['alumni_id' => $alumni->id, 'f8' => 1]);
        }

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)
            ->get(route('reports.auto.download', ['year' => 2025, 'jenjang' => ['S2']]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $summary = app(DashboardService::class)->summary($admin, ['jenjang' => ['S2']]);
        $this->assertSame(1, $summary['data'][2025]['jumlah_alumni']);
    }

    public function test_surveyor_without_export_permission_cannot_view_the_form(): void
    {
        $surveyor = User::factory()->create();
        $surveyor->assignRole('Surveyor');

        $this->actingAs($surveyor)->get(route('reports.auto.form'))->assertForbidden();
    }

    public function test_surveyor_without_export_permission_cannot_download_the_pdf(): void
    {
        $surveyor = User::factory()->create();
        $surveyor->assignRole('Surveyor');

        $this->actingAs($surveyor)->get(route('reports.auto.download'))->assertForbidden();
    }

    public function test_admin_fakultas_only_sees_their_own_faculty_in_the_report(): void
    {
        $ownFaculty = Faculty::factory()->create();
        $otherFaculty = Faculty::factory()->create();
        Alumni::factory()->create(['faculty_id' => $ownFaculty->id, 'graduation_year' => 2024]);
        Alumni::factory()->create(['faculty_id' => $otherFaculty->id, 'graduation_year' => 2024]);

        $admin = User::factory()->create(['faculty_id' => $ownFaculty->id]);
        $admin->assignRole('Admin Fakultas');

        $response = $this->actingAs($admin)->get(route('reports.auto.download', ['year' => 2024]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
