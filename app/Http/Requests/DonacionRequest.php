<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DonacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->exists('estado')) {
            return [
                'estado' => ['required', Rule::in(['recibida', 'verificada', 'rechazada'])],
            ];
        }

        $rules = [
            'producto' => ['required', 'string', 'max:100'],
            'unidad_medida' => ['required', Rule::in(['pieza', 'par', 'kg', 'litro', 'caja', 'paquete', 'kit'])],
            'cantidad' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'descripcion' => ['nullable', 'string'],
            'fecha_recepcion' => ['required', 'date_format:Y-m-d'],
            'evidencia_url' => ['nullable', 'url', 'max:255'],
            'id_campana' => $this->isMethod('post')
                ? ['required', 'integer', Rule::exists('campanas', 'id_campana')->where('estado', 'activa')]
                : ['required', 'integer', Rule::exists('campanas', 'id_campana')],
        ];

        if ($this->isMethod('post')) {
            $rules['id_donador'] = ['required', 'integer', Rule::exists('donadores', 'id_donador')->where('activo', true)];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'producto.required' => 'Escribe el nombre del producto donado.',
            'producto.max' => 'El nombre del producto no puede superar los 100 caracteres.',
            'unidad_medida.required' => 'Selecciona la unidad del producto.',
            'unidad_medida.in' => 'La unidad seleccionada no es válida.',
            'id_donador.required' => 'Selecciona un donador activo.',
            'id_donador.exists' => 'El donador seleccionado no está activo.',
            'id_campana.required' => 'Selecciona una campaña.',
            'id_campana.exists' => 'La campaña seleccionada no está disponible.',
            'fecha_recepcion.required' => 'La fecha de recepción es obligatoria.',
            'fecha_recepcion.date_format' => 'La fecha de recepción no tiene un formato válido.',
            'evidencia_url.url' => 'La URL de evidencia no es válida.',
            'cantidad.required' => 'La cantidad de bienes es obligatoria.',
            'cantidad.numeric' => 'La cantidad debe ser un número.',
            'cantidad.gt' => 'La cantidad debe ser mayor que cero.',
            'estado.required' => 'Selecciona el estado de la donación.',
            'estado.in' => 'El estado solicitado no es válido.',
        ];
    }
}
