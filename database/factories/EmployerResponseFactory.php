<?php

namespace Database\Factories;

use App\Models\Alumni;
use App\Models\EmployerResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployerResponse>
 */
class EmployerResponseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'alumni_id' => Alumni::factory(),
            'nama_pengisi' => fake()->name(),
            'jabatan' => 'HRD Manager',
            'nama_perusahaan' => fake()->company(),
            'alamat_perusahaan' => fake()->address(),
            'no_telp' => fake()->numerify('08##########'),
            'q1_kerja_sama_tim' => 1,
            'q2_pengembangan_diri' => 1,
            'q3_komunikasi' => 1,
            'q4_teknologi_informasi' => 1,
            'q5_bahasa_asing' => 1,
            'q6_keahlian' => 1,
            'q7_integritas' => 1,
        ];
    }
}
