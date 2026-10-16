<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoluntarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $voluntario = $this->route('voluntario');

        return [
            'id_usuario' => [
                'required',
                'integer',
                Rule::exists('usuarios', 'id_usuario'),
                Rule::unique('voluntarios', 'id_usuario')->ignore($voluntario, 'id_voluntario'),
            ],
            'telefono_contacto' => ['nullable', 'string', 'max:20'],
            'zona_asignada' => ['required', Rule::in(['san_martin', 'tlaxcala', 'ambas', 'otra'])],
            'disponibilidad' => ['required', Rule::in(['tiempo_completo', 'medio_tiempo', 'fines_semana', 'bajo_demanda'])],
            'fecha_ingreso' => ['required', 'date'],
            'notas' => ['nullable', 'string'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_usuario.required' => 'Selecciona un usuario para el perfil de voluntario.',
            'id_usuario.exists' => 'El usuario seleccionado no existe.',
            'id_usuario.unique' => 'Este usuario ya tiene un perfil de voluntario.',
            'telefono_contacto.max' => 'El teléfono no puede superar 20 caracteres.',
            'zona_asignada.required' => 'Selecciona una zona.',
            'zona_asignada.in' => 'La zona seleccionada no es válida.',
            'disponibilidad.required' => 'Selecciona la disponibilidad.',
            'disponibilidad.in' => 'La disponibilidad seleccionada no es válida.',
            'fecha_ingreso.required' => 'La fecha de ingreso es obligatoria.',
            'fecha_ingreso.date' => 'La fecha de ingreso no es válida.',
        ];
    }
}
