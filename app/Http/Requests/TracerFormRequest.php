<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Conditional validation rules mirror "Panduan Form - Tracer Study.pdf" (the
 * national Kemdiktisaintek tracer form) exactly — each field's required_if
 * matches that PDF's "Instrumen ditampilkan jika ..." / "... tidak boleh
 * kosong jika ..." notes, branch by branch:
 *   F8=1 bekerja, F8=2 belum memungkinkan bekerja, F8=3 wirausaha,
 *   F8=4 melanjutkan pendidikan, F8=5 mencari kerja.
 */
class TracerFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fillTracer', $this->route('alumni'));
    }

    public function rules(): array
    {
        $ratingScale5 = ['required', 'integer', 'between:1,5'];

        // F301's option set differs by branch: only "bekerja" (f8=1) offers
        // "Saya tidak mencari pekerjaan" as a third choice. Cast before
        // comparing — a real form submission sends f8 as a string, but a
        // test (or any JSON client) may hand it over as an int.
        $f301Options = (string) $this->input('f8') === '1' ? 'in:1,2,3' : 'in:1,2';

        $rules = [
            'f8' => ['required', 'integer', 'in:1,2,3,4,5'],

            // Masa tunggu kerja / mulai wirausaha — shared field, bekerja (1) & wirausaha (3).
            'f502' => ['nullable', 'integer', 'min:0', 'required_if:f8,1,3'],
            'f505' => ['nullable', 'numeric', 'min:0', 'required_if:f8,1,3'],

            // Lokasi (F510/F5A1/F5A2) — bekerja (1), wirausaha (3), atau studi lanjut (4).
            'work_province_id' => ['nullable', 'exists:provinces,id', 'required_if:f8,1,3,4'],
            'work_city_id' => ['nullable', 'exists:cities,id', 'required_if:f8,1,3,4'],

            // Bekerja (1) only.
            'f1101' => ['nullable', 'integer', 'in:1,2,3,5,6,7', 'required_if:f8,1'],
            'f1102' => ['nullable', 'string', 'max:255', 'required_if:f1101,5'],

            // Nama perusahaan/wirausaha & ruang lingkup — shared, bekerja (1) & wirausaha (3).
            'f5b' => ['nullable', 'string', 'max:255', 'required_if:f8,1,3'],
            'f5d' => ['nullable', 'integer', 'in:1,2,3', 'required_if:f8,1,3'],

            // Wirausaha (3) only.
            'f5c' => ['nullable', 'integer', 'in:1,2,4', 'required_if:f8,3'],
            'f5e' => ['nullable', 'integer', 'in:0,1', 'required_if:f8,3'],

            // Melanjutkan pendidikan (4) only.
            'f18a' => ['nullable', 'integer', 'in:1,2', 'required_if:f8,4'],
            'f18b' => ['nullable', 'string', 'max:255', 'required_if:f8,4'],
            'f18c' => ['nullable', 'string', 'max:255', 'required_if:f8,4'],
            'f18d' => ['nullable', 'date', 'required_if:f8,4'],

            // Selalu wajib, apa pun jawaban F8.
            'f1201' => ['required', 'integer', 'in:1,2,3,4,5,6,7,8'],
            'f1202' => ['nullable', 'string', 'max:255', 'required_if:f1201,7'],

            // Relevansi — bekerja (1), wirausaha (3), studi lanjut (4, hanya F14; F15 tidak ada di cabang ini).
            'f14' => ['nullable', 'integer', 'between:1,5', 'required_if:f8,1,3,4'],
            'f15' => ['nullable', 'integer', 'between:1,4', 'required_if:f8,1,3'],

            // Kapan mulai mencari kerja/wirausaha — bekerja (1), wirausaha (3), mencari kerja (5).
            'f301' => ['nullable', 'integer', $f301Options, 'required_if:f8,1,3,5'],
            'f302' => ['nullable', 'integer', 'min:0', 'required_if:f301,1'],
            'f303' => ['nullable', 'integer', 'min:0', 'required_if:f301,2'],

            'f416' => ['nullable', 'string', 'max:255'],

            // F4/F6/F7/F7A/F1001 (cara & intensitas mencari kerja) — bekerja (1) & mencari kerja (5).
            'f6' => ['nullable', 'integer', 'min:0', 'required_if:f8,1,5'],
            'f7' => ['nullable', 'integer', 'min:0', 'required_if:f8,1,5'],
            'f7a' => ['nullable', 'integer', 'min:0', 'required_if:f8,1,5'],
            'f1001' => ['nullable', 'integer', 'in:1,2,3,4,5', 'required_if:f8,1,5'],
            'f1002' => ['nullable', 'string', 'max:255', 'required_if:f1001,5'],
            'f1614' => ['nullable', 'string', 'max:255'],
        ];

        // Kompetensi (F17A/F17B, 11 pasang) dan metode pembelajaran (F2, F21-F37)
        // selalu wajib diisi, tidak tergantung jawaban F8.
        foreach (array_merge(range(1761, 1782), range(21, 37)) as $code) {
            $rules["f{$code}"] = $ratingScale5;
        }

        foreach (array_merge(range(401, 415), range(1601, 1613)) as $code) {
            $rules["f{$code}"] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            if ($this->boolean('f415') && ! $this->filled('f416')) {
                $validator->errors()->add('f416', 'Wajib diisi jika memilih "Lainnya".');
            }

            if ($this->boolean('f1613') && ! $this->filled('f1614')) {
                $validator->errors()->add('f1614', 'Wajib diisi jika memilih "Lainnya".');
            }

            $f8 = (string) $this->input('f8');

            // F4 (cara mencari kerja): minimal satu dipilih jika F8 bekerja atau mencari kerja.
            if (in_array($f8, ['1', '5'], true) && ! $this->anyChecked([401, 402, 403, 404, 405, 406, 407, 408, 409, 411, 413, 414, 415])) {
                $validator->errors()->add('f401', 'Pilih minimal satu cara mencari pekerjaan.');
            }

            // F16 (alasan ketidaksesuaian): minimal satu dipilih jika F8 bekerja atau wirausaha.
            if (in_array($f8, ['1', '3'], true) && ! $this->anyChecked([1601, 1602, 1603, 1604, 1605, 1606, 1607, 1608, 1609, 1610, 1612, 1613])) {
                $validator->errors()->add('f1601', 'Pilih minimal satu alasan.');
            }
        });
    }

    /**
     * @param  list<int>  $codes
     */
    private function anyChecked(array $codes): bool
    {
        foreach ($codes as $code) {
            if ($this->boolean("f{$code}")) {
                return true;
            }
        }

        return false;
    }
}
