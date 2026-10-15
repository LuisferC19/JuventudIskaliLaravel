<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BeneficiarioRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'curp' => strtoupper(trim((string) $this->input('curp'))) ?: null,
            'rfc' => strtoupper(trim((string) $this->input('rfc'))) ?: null,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $beneficiarioId = $this->route('beneficiario');
        $tipo = $this->input('tipo_persona');

        return [
            'tipo_persona' => ['required', Rule::in(['fisica', 'moral'])],
            'id_comunidad' => ['required', 'integer', 'exists:comunidades,id_comunidad'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:15', 'regex:/^(?=(?:\D*\d){10}\D*$)[0-9()+\-\s]+$/'],
            'estado' => ['required', Rule::in(['activo', 'inactivo', 'en_espera'])],
            'nombre' => [Rule::requiredIf($tipo === 'fisica'), 'nullable', 'string', 'min:2', 'max:100'],
            'apellido' => [Rule::requiredIf($tipo === 'fisica'), 'nullable', 'string', 'min:2', 'max:100'],
            'edad' => ['nullable', 'integer', 'min:0', 'max:150'],
            'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'curp' => [
                'nullable',
                'string',
                'regex:/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9][0-9]$/',
                Rule::unique('beneficiarios_fisicos', 'curp')->ignore($beneficiarioId, 'id_beneficiario'),
            ],
            'razon_social' => [Rule::requiredIf($tipo === 'moral'), 'nullable', 'string', 'min:3', 'max:150'],
            'rfc' => [
                'nullable',
                'string',
                'regex:/^[A-Z&Ñ]{3}[0-9]{6}[A-Z0-9]{3}$/u',
                Rule::unique('beneficiarios_morales', 'rfc')->ignore($beneficiarioId, 'id_beneficiario'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_persona.required' => 'Selecciona el tipo de beneficiario.',
            'tipo_persona.in' => 'El tipo de beneficiario seleccionado no es válido.',
            'id_comunidad.required' => 'Selecciona una comunidad.',
            'id_comunidad.exists' => 'La comunidad seleccionada no existe.',
            'telefono.regex' => 'El teléfono debe contener 10 dígitos y solo puede usar espacios, +, - o paréntesis.',
            'estado.required' => 'Selecciona el estado del beneficiario.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'nombre.required' => 'El nombre es obligatorio para una persona física.',
            'apellido.required' => 'El apellido es obligatorio para una persona física.',
            'edad.min' => 'La edad no puede ser negativa.',
            'edad.max' => 'La edad no puede superar 150 años.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
            'curp.regex' => 'La CURP debe tener 18 caracteres y un formato válido.',
            'curp.unique' => 'La CURP ya está registrada.',
            'razon_social.required' => 'La razón social es obligatoria para una persona moral.',
            'rfc.regex' => 'El RFC debe tener 12 caracteres y un formato válido.',
            'rfc.unique' => 'El RFC ya está registrado.',
        ];
    }
}
