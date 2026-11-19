<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CanjeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_voluntario' => [
                'required',
                'integer',
                Rule::exists('voluntarios', 'id_voluntario')->where('activo', true),
            ],
            'id_premio' => [
                'required',
                'integer',
                Rule::exists('cat_premios', 'id_premio')->where('activo', true),
            ],
        ];
    }
}
