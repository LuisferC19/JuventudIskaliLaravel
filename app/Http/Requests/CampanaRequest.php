<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampanaRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => trim((string) $this->input('nombre')),
            'meta_economica' => trim((string) $this->input('meta_economica', '')) === ''
                ? '0'
                : $this->input('meta_economica'),
            'imagen_url' => trim((string) $this->input('imagen_url')) ?: null,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:2', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'tipo_meta' => ['required', Rule::in(['economica', 'especie', 'mixta', 'servicio'])],
            'meta_economica' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_cierre' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'estado' => $this->isMethod('post')
                ? ['sometimes', Rule::in(['borrador'])]
                : ['prohibited'],
            'imagen_url' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la campaña es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no puede superar los 150 caracteres.',
            'tipo_meta.required' => 'Selecciona el tipo de meta.',
            'tipo_meta.in' => 'El tipo de meta seleccionado no es válido.',
            'meta_economica.required' => 'La meta económica es obligatoria; usa 0 si no aplica.',
            'meta_economica.numeric' => 'La meta económica debe ser un número.',
            'meta_economica.min' => 'La meta económica no puede ser negativa.',
            'meta_economica.max' => 'La meta económica supera el máximo permitido.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date_format' => 'La fecha de inicio debe tener un formato válido.',
            'fecha_cierre.required' => 'La fecha de cierre es obligatoria.',
            'fecha_cierre.date_format' => 'La fecha de cierre debe tener un formato válido.',
            'fecha_cierre.after_or_equal' => 'La fecha de cierre debe ser igual o posterior a la fecha de inicio.',
            'estado.in' => 'Una campaña nueva debe iniciar en estado borrador.',
            'estado.prohibited' => 'El estado solo puede cambiarse mediante las transiciones disponibles.',
            'imagen_url.url' => 'Ingresa una URL de imagen válida.',
            'imagen_url.max' => 'La URL de imagen no puede superar los 255 caracteres.',
        ];
    }
}
