<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Province;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Covers CountriesImport/ProvincesImport/CitiesImport, which all read the
 * same column shapes as the official "master wilayah negara provinsi kota
 * kabupaten" spreadsheet's three sheets (Daftar Negara / Daftar Provinsi /
 * Daftar Kota atau Kabupaten).
 */
class MasterWilayahImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function csvFile(string $filename, array $headers, array $rows): UploadedFile
    {
        $lines = [implode(',', $headers)];

        foreach ($rows as $row) {
            $lines[] = implode(',', $row);
        }

        return UploadedFile::fake()->createWithContent($filename, implode("\n", $lines));
    }

    public function test_admin_universitas_can_import_countries(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $file = $this->csvFile('negara.csv', ['Kode Wilayah Negara', 'Negara'], [
            ['ID', 'Indonesia'],
            ['AE', 'United Arab Emirates'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.countries.import'), ['file' => $file]);

        $response->assertRedirect(route('admin.countries.index'));
        $this->assertDatabaseHas('countries', ['code' => 'ID', 'name' => 'Indonesia']);
        $this->assertDatabaseHas('countries', ['code' => 'AE', 'name' => 'United Arab Emirates']);
    }

    public function test_importing_provinces_strips_the_prov_prefix_and_links_the_country(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');
        $indonesia = Country::factory()->create(['code' => 'ID', 'name' => 'Indonesia']);

        $file = $this->csvFile('provinsi.csv', ['Kode Wilayah Negara', 'Negara', 'Kode Wilayah Provinsi', 'Provinsi'], [
            ['ID', 'Indonesia', '010000', 'Prov. D.K.I. Jakarta'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.provinces.import'), ['file' => $file]);

        $response->assertRedirect(route('admin.provinces.index'));
        $this->assertDatabaseHas('provinces', [
            'code' => '010000',
            'name' => 'D.K.I. Jakarta',
            'country_id' => $indonesia->id,
        ]);
    }

    public function test_importing_a_province_that_already_exists_updates_its_code_instead_of_duplicating_it(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');
        Country::factory()->create(['code' => 'ID', 'name' => 'Indonesia']);

        // Pre-existing row under the old BPS-style code, matching by name only.
        $existing = Province::factory()->create(['code' => '33', 'name' => 'Jawa Tengah']);

        $file = $this->csvFile('provinsi.csv', ['Kode Wilayah Negara', 'Negara', 'Kode Wilayah Provinsi', 'Provinsi'], [
            ['ID', 'Indonesia', '030000', 'Prov. Jawa Tengah'],
        ]);

        $this->actingAs($admin)->post(route('admin.provinces.import'), ['file' => $file]);

        $this->assertSame(1, Province::where('name', 'Jawa Tengah')->count());
        $this->assertDatabaseHas('provinces', ['id' => $existing->id, 'code' => '030000']);
    }

    public function test_importing_cities_matches_the_province_by_code_and_creates_the_city(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');
        $province = Province::factory()->create(['code' => '020000', 'name' => 'Jawa Barat']);

        $file = $this->csvFile(
            'kota.csv',
            ['Kode Wilayah Negara', 'Negara', 'Kode Wilayah Provinsi', 'Provinsi', 'Kode Wilayah Kota/Kabupaten', 'Kota/Kabupaten'],
            [['ID', 'Indonesia', '020000', 'Prov. Jawa Barat', '020500', 'Kab. Bekasi']]
        );

        $response = $this->actingAs($admin)->post(route('admin.cities.import'), ['file' => $file]);

        $response->assertRedirect(route('admin.cities.index'));
        $this->assertDatabaseHas('cities', [
            'province_id' => $province->id,
            'code' => '020500',
            'name' => 'Kab. Bekasi',
        ]);
    }

    public function test_provinces_import_skips_rows_shaped_like_the_negara_sheet(): void
    {
        // The official master-wilayah workbook has 3 sheets; Laravel Excel
        // re-reads the heading row per sheet, so uploading the whole file to
        // any one of the three import endpoints makes that endpoint's
        // collection() receive rows from the OTHER sheets too, each shaped
        // according to their own (different) heading row. Each import must
        // only act on rows matching its own expected column count.
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');
        Country::factory()->create(['code' => 'ID', 'name' => 'Indonesia']);

        $file = $this->csvFile('negara-shaped.csv', ['Kode Wilayah Negara', 'Negara'], [
            ['ID', 'Indonesia'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.provinces.import'), ['file' => $file]);

        $response->assertRedirect(route('admin.provinces.index'));
        $this->assertDatabaseMissing('provinces', ['name' => 'Indonesia']);
    }

    public function test_admin_universitas_can_create_edit_and_delete_a_country(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Universitas');

        $this->actingAs($admin)->post(route('admin.countries.store'), ['code' => 'SG', 'name' => 'Singapore'])
            ->assertRedirect(route('admin.countries.index'));
        $country = Country::where('code', 'SG')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.countries.update', $country), ['code' => 'SG', 'name' => 'Singapura'])
            ->assertRedirect(route('admin.countries.index'));
        $this->assertDatabaseHas('countries', ['id' => $country->id, 'name' => 'Singapura']);

        $this->actingAs($admin)->delete(route('admin.countries.destroy', $country))
            ->assertRedirect(route('admin.countries.index'));
        $this->assertDatabaseMissing('countries', ['id' => $country->id]);
    }

    public function test_only_super_admin_and_admin_universitas_can_import_master_wilayah_data(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Fakultas');

        $file = $this->csvFile('negara.csv', ['Kode Wilayah Negara', 'Negara'], [['ID', 'Indonesia']]);

        $this->actingAs($admin)->post(route('admin.countries.import'), ['file' => $file])->assertForbidden();
    }
}
