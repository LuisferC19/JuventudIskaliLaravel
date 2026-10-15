<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AjustarInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_tipo_bien' => ['required', 'integer', 'min:1', 'exists:tipos_bien,id_tipo_bien'],
            'cantidad_total_recibida' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'cantidad_total_entregada' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'motivo' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_tipo_bien.required' => 'Selecciona un tipo de bien.',
            'cantidad_total_recibida.required' => 'La cantidad recibida es obligatoria.',
            'cantidad_total_recibida.decimal' => 'La cantidad recibida admite hasta dos decimales.',
            'cantidad_total_entregada.required' => 'La cantidad entregada es obligatoria.',
            'cantidad_total_entregada.decimal' => 'La cantidad entregada admite hasta dos decimales.',
            'motivo.required' => 'El motivo del ajuste es obligatorio.',
            'motivo.max' => 'El motivo no puede superar los 2000 caracteres.',
        ];
    }
}
