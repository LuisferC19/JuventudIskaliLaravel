<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'puntos' => ['required', 'integer', 'between:5,20'],
            'id_usuario_responsable' => [
                'nullable',
                'integer',
                Rule::exists('usuarios', 'id_usuario')->where('estatus', 'ACTIVO'),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $usuario = $this->user();
            if (
                $usuario?->esRol('coordinador')
                && in_array($usuario->sede, ['san_martin', 'tlaxcala'], true)
                && $this->input('zona') !== $usuario->sede
            ) {
                $validator->errors()->add('zona', 'Solo puedes programar actividades en tu sede asignada.');
            }
        });
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
            'puntos.required' => 'Los puntos son obligatorios.',
            'puntos.integer' => 'Los puntos deben ser un número entero.',
            'puntos.between' => 'Los puntos deben estar entre 5 y 20.',
            'id_usuario_responsable.exists' => 'El usuario responsable seleccionado no existe.',
        ];
    }
}
