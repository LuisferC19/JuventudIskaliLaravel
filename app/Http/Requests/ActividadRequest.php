<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'zona' => ['required', 'in:san_martin,tlaxcala,ambas'],
            'estado' => ['required', 'in:planeada,en_curso,completada,cancelada'],
            'id_usuario_responsable' => [
                'nullable',
                'integer',
                Rule::exists('usuarios', 'id_usuario')->where('activo', true),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El título de la actividad es obligatorio.',
            'titulo.max' => 'El título no puede exceder 150 caracteres.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio no es válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'zona.required' => 'Selecciona una zona.',
            'zona.in' => 'La zona seleccionada no es válida.',
            'estado.required' => 'Selecciona el estado de la actividad.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'id_usuario_responsable.exists' => 'El usuario responsable seleccionado no existe.',
        ];
    }
}
