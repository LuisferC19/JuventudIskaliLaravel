<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsignacionVoluntarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->exists('estado')) {
            return [
                'estado' => ['required', Rule::in(['asignada', 'aceptada', 'rechazada', 'completada', 'cancelada'])],
                'fecha_compromiso' => ['sometimes', 'required', 'date'],
                'observaciones_voluntario' => ['nullable', 'string'],
            ];
        }

        return [
            'id_voluntario' => ['required', 'integer', Rule::exists('voluntarios', 'id_voluntario')->where('activo', true)],
            'id_entrega' => ['required', 'integer', Rule::exists('entregas', 'id_entrega')->whereIn('estado', ['programada', 'en_proceso'])],
            'fecha_compromiso' => ['required', 'date'],
            'observaciones_voluntario' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_voluntario.required' => 'Selecciona un voluntario activo.',
            'id_voluntario.exists' => 'El voluntario seleccionado no existe o está inactivo.',
            'id_entrega.required' => 'Selecciona una entrega programada o en proceso.',
            'id_entrega.exists' => 'La entrega seleccionada no existe o no está disponible.',
            'fecha_compromiso.required' => 'La fecha compromiso es obligatoria.',
            'fecha_compromiso.date' => 'La fecha compromiso no es válida.',
            'estado.in' => 'El estado de la asignación no es válido.',
        ];
    }
}
