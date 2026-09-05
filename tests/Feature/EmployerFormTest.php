<?php

namespace Tests\Feature;

use App\Models\Alumni;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmployerFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_employer_can_view_and_submit_the_form_without_logging_in(): void
    {
        $alumni = Alumni::factory()->create();

        $url = URL::temporarySignedRoute('employer.show', now()->addDays(30), ['alumni' => $alumni->id]);

        $this->get($url)->assertOk();

        $response = $this->post($url, [
            'nama_pengisi' => 'Budi Santoso',
            'jabatan' => 'HRD Manager',
            'nama_perusahaan' => 'PT Contoh Sejahtera',
            'q1_kerja_sama_tim' => 1,
            'q2_pengembangan_diri' => 1,
            'q3_komunikasi' => 2,
            'q4_teknologi_informasi' => 2,
            'q5_bahasa_asing' => 3,
            'q6_keahlian' => 1,
            'q7_integritas' => 1,
        ]);

        $response->assertRedirect(route('employer.thanks'));
        $this->get(route('employer.thanks'))->assertOk();
        $this->assertDatabaseHas('employer_responses', [
            'alumni_id' => $alumni->id,
            'nama_pengisi' => 'Budi Santoso',
        ]);
    }

    public function test_unsigned_url_is_rejected(): void
    {
        $alumni = Alumni::factory()->create();

        $this->get(route('employer.show', ['alumni' => $alumni->id]))->assertForbidden();
    }
}
