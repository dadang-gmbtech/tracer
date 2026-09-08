<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_universitas_can_view_reports_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)->get(route('reports.index'))->assertOk();
    }

    public function test_alumni_is_redirected_away_from_reports(): void
    {
        $alumni = Alumni::factory()->create();
        $user = User::factory()->create(['nim' => $alumni->nim]);
        $user->assignRole('Alumni');

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertRedirect(route('tracer.edit', $alumni));
    }

    public function test_alumni_cannot_upload_a_report(): void
    {
        $alumni = Alumni::factory()->create();
        $user = User::factory()->create(['nim' => $alumni->nim]);
        $user->assignRole('Alumni');

        $this->actingAs($user)->get(route('reports.create'))->assertForbidden();
    }

    public function test_downloading_a_report_keeps_the_files_extension_even_though_the_title_has_none(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)->post(route('reports.store'), [
            'title' => 'Laporan Kinerja Tahunan', // no extension, unlike the underlying file
            'year' => 2024,
            'file' => UploadedFile::fake()->create('asli.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('reports.index'));

        $report = Report::firstOrFail();

        $response = $this->actingAs($admin)->get(route('reports.download', $report));

        $response->assertOk();
        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('Laporan Kinerja Tahunan.pdf', $disposition);
    }
}
