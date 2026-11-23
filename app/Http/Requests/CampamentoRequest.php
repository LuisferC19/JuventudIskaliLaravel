<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CampamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'zona' => ['required', 'in:san_martin,tlaxcala,ambas'],
            'cupo' => ['nullable', 'integer', 'min:1'],
            'umbral_puntos' => [
                'required',
                'integer',
                'between:'.(int) config('iskali.umbral_minimo').','.(int) config('iskali.umbral_maximo'),
            ],
            'estado' => ['sometimes', 'in:abierto,cerrado,cancelado'],
        ];
    }

    public function messages(): array
    {
        return [
            'umbral_puntos.between' => 'El umbral debe estar entre 50 y 200 puntos.',
        ];
    }
}
