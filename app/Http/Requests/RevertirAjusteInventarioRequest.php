<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RevertirAjusteInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_tipo_bien' => ['required', 'integer', 'min:1', 'exists:tipos_bien,id_tipo_bien'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_tipo_bien.required' => 'Selecciona el tipo de bien cuyo ajuste deseas revertir.',
            'id_tipo_bien.exists' => 'El tipo de bien seleccionado no existe.',
        ];
    }
}
