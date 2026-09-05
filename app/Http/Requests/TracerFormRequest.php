<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Conditional validation rules mirror Form Tracer Studi.pdf exactly (the
 * "Jika Memilih ... / Jika f8 adalah ..." notes next to each question).
 */
class TracerFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fillTracer', $this->route('alumni'));
    }

    public function rules(): array
    {
        $ratingScale5 = ['nullable', 'integer', 'between:1,5'];

        $rules = [
            'f8' => ['required', 'integer', 'in:1,2,3,4,5'],
            'f502' => ['nullable', 'integer', 'min:0', 'required_if:f8,1,3'],
            'f505' => ['nullable', 'numeric', 'min:0'],
            'work_province_id' => ['nullable', 'exists:provinces,id', 'required_if:f8,1,3'],
            'work_city_id' => ['nullable', 'exists:cities,id', 'required_if:f8,1,3'],
            'f1101' => ['nullable', 'integer', 'in:1,2,3,4,5,6,7', 'required_if:f8,1'],
            'f1102' => ['nullable', 'string', 'max:255', 'required_if:f1101,5'],
            'f5b' => ['nullable', 'string', 'max:255'],
            'f5c' => ['nullable', 'integer', 'in:1,2,3,4', 'required_if:f8,3'],
            'f5d' => ['nullable', 'integer', 'in:1,2,3', 'required_if:f8,3'],
            'f18a' => ['nullable', 'integer', 'in:1,2', 'required_if:f8,4'],
            'f18b' => ['nullable', 'string', 'max:255', 'required_if:f8,4'],
            'f18c' => ['nullable', 'string', 'max:255', 'required_if:f8,4'],
            'f18d' => ['nullable', 'string', 'max:255', 'required_if:f8,4'],
            'f1201' => ['required', 'integer', 'in:1,2,3,4,5,6,7'],
            'f1202' => ['nullable', 'string', 'max:255', 'required_if:f1201,7'],
            'f14' => ['nullable', 'integer', 'between:1,5', 'required_if:f8,1'],
            'f15' => ['nullable', 'integer', 'between:1,4', 'required_if:f8,1'],
            'f301' => ['required', 'integer', 'in:1,2,3'],
            'f302' => ['nullable', 'integer', 'min:0', 'required_if:f301,1'],
            'f303' => ['nullable', 'integer', 'min:0', 'required_if:f301,2'],
            'f416' => ['nullable', 'string', 'max:255'],
            'f6' => ['nullable', 'integer', 'min:0'],
            'f7' => ['nullable', 'integer', 'min:0'],
            'f7a' => ['nullable', 'integer', 'min:0'],
            'f1001' => ['nullable', 'integer', 'between:1,5'],
            'f1002' => ['nullable', 'string', 'max:255', 'required_if:f1001,5'],
            'f1614' => ['nullable', 'string', 'max:255'],
        ];

        foreach (array_merge(range(1761, 1774), range(21, 27)) as $code) {
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
        });
    }
}
