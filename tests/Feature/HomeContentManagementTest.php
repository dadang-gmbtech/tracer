<?php

namespace Tests\Feature;

use App\Models\HomePageContent;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
