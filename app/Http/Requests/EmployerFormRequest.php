<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployerFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // access is gated by the signed-URL middleware on the route
    }

    public function rules(): array
    {
        $rating = ['required', 'integer', 'in:1,2,3,4'];

        return [
            'nama_pengisi' => ['required', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'nama_perusahaan' => ['required', 'string', 'max:255'],
            'alamat_perusahaan' => ['nullable', 'string', 'max:1000'],
            'no_telp' => ['nullable', 'string', 'max:50'],
            'q1_kerja_sama_tim' => $rating,
            'q2_pengembangan_diri' => $rating,
            'q3_komunikasi' => $rating,
            'q4_teknologi_informasi' => $rating,
            'q5_bahasa_asing' => $rating,
            'q6_keahlian' => $rating,
            'q7_integritas' => $rating,
        ];
    }
}
