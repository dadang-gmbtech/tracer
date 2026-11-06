<?php

namespace Tests\Feature;

use App\Models\HomePageContent;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeContentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'hero_title' => 'Judul Baru',
            'hero_subtitle' => 'Sub Judul Baru',
            'hero_description' => 'Deskripsi hero baru.',
            'tentang_description' => 'Deskripsi tentang baru.',
            'tentang_cards' => [
                ['title' => 'Kartu Satu', 'description' => 'Isi kartu satu.'],
                ['title' => 'Kartu Dua', 'description' => 'Isi kartu dua.'],
                ['title' => 'Kartu Tiga', 'description' => 'Isi kartu tiga.'],
            ],
            'alur_steps' => ['Langkah satu.', 'Langkah dua.', 'Langkah tiga.', 'Langkah empat.'],
            'footer_address' => 'Alamat baru.',
        ], $overrides);
    }

    public function test_super_admin_can_view_the_edit_form(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)
            ->get(route('admin.home-content.edit'))
            ->assertOk()
            ->assertSee('Konten Halaman Depan');
    }

    public function test_super_admin_can_update_the_home_page_content(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->put(route('admin.home-content.update'), $this->validPayload());

        $response->assertRedirect(route('admin.home-content.edit'));
        $this->assertSame('Judul Baru', HomePageContent::current()->hero_title);
        $this->assertSame('Kartu Dua', HomePageContent::current()->tentang_cards[1]['title']);
    }

    public function test_the_public_home_page_reflects_saved_content(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $this->actingAs($admin)->put(route('admin.home-content.update'), $this->validPayload(['hero_title' => 'Judul Unik Sekali']));
        $this->post(route('logout'));

        $this->get('/')->assertOk()->assertSee('Judul Unik Sekali');
    }

    public function test_admin_universitas_cannot_manage_home_content(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)->get(route('admin.home-content.edit'))->assertForbidden();
        $this->actingAs($admin)->put(route('admin.home-content.update'), $this->validPayload())->assertForbidden();
    }

    public function test_tentang_cards_must_have_exactly_three_entries(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->put(route('admin.home-content.update'), $this->validPayload([
            'tentang_cards' => [['title' => 'Hanya Satu', 'description' => 'Saja.']],
        ]));

        $response->assertSessionHasErrors('tentang_cards');
    }

    public function test_alur_steps_must_have_exactly_four_entries(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->put(route('admin.home-content.update'), $this->validPayload([
            'alur_steps' => ['Cuma satu.'],
        ]));

        $response->assertSessionHasErrors('alur_steps');
    }

    private function report(string $level, string $title): Report
    {
        return Report::create([
            'level' => $level,
            'title' => $title,
            'file_path' => 'laporan-tracer/'.str()->slug($title).'.pdf',
            'year' => 2025,
            'uploaded_by' => User::factory()->create()->id,
        ]);
    }

    public function test_the_reports_section_is_off_until_switched_on(): void
    {
        $this->report(Report::LEVEL_UNIVERSITAS, 'Laporan Universitas 2025');

        $this->get(route('home'))->assertOk()->assertDontSee('Laporan Universitas 2025');

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)->get(route('admin.home-content.edit'))->assertSee('Tidak Aktif')->assertSee('Aktifkan');
        $this->actingAs($admin)->patch(route('admin.home-content.reports-visibility'), ['show_reports' => 1])
            ->assertRedirect(route('admin.home-content.edit').'#laporan-tracer');
        $this->assertTrue(HomePageContent::current()->show_reports);
        auth()->logout();

        $this->get(route('home'))->assertOk()->assertSee('Laporan Universitas 2025');

        $this->actingAs($admin)->patch(route('admin.home-content.reports-visibility'), ['show_reports' => 0]);
        auth()->logout();

        $this->get(route('home'))->assertOk()->assertDontSee('Laporan Universitas 2025');
    }

    public function test_saving_the_content_form_leaves_the_reports_switch_alone(): void
    {
        HomePageContent::current()->update(['show_reports' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)->put(route('admin.home-content.update'), $this->validPayload());

        $this->assertTrue(HomePageContent::current()->show_reports);
    }

    public function test_admin_universitas_cannot_switch_the_reports_section(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)->patch(route('admin.home-content.reports-visibility'), ['show_reports' => 1])->assertForbidden();
        $this->assertFalse(HomePageContent::current()->show_reports);
    }

    public function test_only_university_level_reports_are_listed_and_downloadable(): void
    {
        Storage::fake('public');
        $universitas = $this->report(Report::LEVEL_UNIVERSITAS, 'Laporan Universitas 2025');
        $fakultas = $this->report(Report::LEVEL_FAKULTAS, 'Laporan Fakultas Teknik');
        Storage::disk('public')->put($universitas->file_path, 'isi');
        Storage::disk('public')->put($fakultas->file_path, 'isi');
        HomePageContent::current()->update(['show_reports' => true]);

        $this->get(route('home'))
            ->assertSee('Laporan Universitas 2025')
            ->assertDontSee('Laporan Fakultas Teknik');

        $this->get(route('home.reports.download', $universitas))->assertOk()->assertDownload('Laporan Universitas 2025.pdf');
        $this->get(route('home.reports.download', $fakultas))->assertNotFound();
    }

    public function test_reports_cannot_be_downloaded_publicly_while_the_section_is_off(): void
    {
        Storage::fake('public');
        $universitas = $this->report(Report::LEVEL_UNIVERSITAS, 'Laporan Universitas 2025');
        Storage::disk('public')->put($universitas->file_path, 'isi');

        $this->get(route('home.reports.download', $universitas))->assertNotFound();
    }
}
