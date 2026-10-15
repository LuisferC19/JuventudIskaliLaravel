<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstadoCampanaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in(['activa', 'pausada', 'cerrada', 'cancelada'])],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Selecciona el nuevo estado de la campaña.',
            'estado.in' => 'El estado solicitado no es válido.',
        ];
    }
}
