<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PenalizacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_voluntario' => ['required', 'integer', 'exists:voluntarios,id_voluntario'],
            'id_actividad' => ['required', 'integer', 'exists:actividades,id_actividad'],
            'tipo' => ['required', 'in:retardo,inasistencia_injustificada'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }
}
