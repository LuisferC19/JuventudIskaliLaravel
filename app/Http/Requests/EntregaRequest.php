<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->exists('estado')) {
            return [
                'estado' => ['required', Rule::in(['en_proceso', 'completada', 'cancelada'])],
            ];
        }

        $rules = [
            'id_beneficiario' => ['required', 'integer', Rule::exists('beneficiarios', 'id_beneficiario')],
            'cantidad_entregada' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'fecha_entrega' => ['required', 'date_format:Y-m-d\TH:i'],
            'evidencia_url' => ['nullable', 'url', 'max:255'],
            'observaciones' => ['nullable', 'string'],
        ];

        if ($this->isMethod('post')) {
            $rules['id_donacion'] = [
                'required',
                'integer',
                Rule::exists('donaciones', 'id_donacion')->where('estado', 'verificada'),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'id_donacion.required' => 'Selecciona una donación verificada.',
            'id_donacion.exists' => 'Solo se pueden elegir donaciones verificadas.',
            'id_beneficiario.required' => 'Selecciona un beneficiario.',
            'id_beneficiario.exists' => 'El beneficiario seleccionado no existe.',
            'cantidad_entregada.required' => 'La cantidad entregada es obligatoria.',
            'cantidad_entregada.numeric' => 'La cantidad entregada debe ser un número.',
            'cantidad_entregada.gt' => 'La cantidad entregada debe ser mayor que cero.',
            'fecha_entrega.required' => 'La fecha de entrega es obligatoria.',
            'fecha_entrega.date_format' => 'La fecha de entrega no tiene un formato válido.',
            'evidencia_url.url' => 'La URL de evidencia no es válida.',
            'estado.required' => 'Selecciona el nuevo estado de la entrega.',
            'estado.in' => 'El estado solicitado no es válido.',
        ];
    }
}
