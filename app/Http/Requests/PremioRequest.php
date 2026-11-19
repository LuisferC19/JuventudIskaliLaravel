<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PremioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'costo_puntos' => ['required', 'integer', 'min:1', 'max:100000'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
