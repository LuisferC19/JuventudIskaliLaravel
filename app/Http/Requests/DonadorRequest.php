<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DonadorRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'curp' => strtoupper(trim((string) $this->input('curp'))) ?: null,
            'rfc' => strtoupper(trim((string) $this->input('rfc'))) ?: null,
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $donadorId = $this->route('donador');
        $tipo = $this->input('tipo_persona');

        return [
            'tipo_persona' => ['required', Rule::in(['fisica', 'moral', 'grupo', 'anonimo'])],
            'nombre' => [Rule::requiredIf($tipo === 'fisica'), 'nullable', 'string', 'min:2', 'max:100'],
            'apellido' => [Rule::requiredIf($tipo === 'fisica'), 'nullable', 'string', 'min:2', 'max:100'],
            'curp' => [
                'nullable',
                'string',
                'regex:/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9][0-9]$/',
                Rule::unique('donadores_fisicos', 'curp')->ignore($donadorId, 'id_donador'),
            ],
            'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'razon_social' => [Rule::requiredIf($tipo === 'moral'), 'nullable', 'string', 'min:3', 'max:150'],
            'rfc' => [
                'nullable',
                'string',
                'regex:/^[A-Z&Ñ]{3}[0-9]{6}[A-Z0-9]{3}$/u',
                Rule::unique('donadores_morales', 'rfc')->ignore($donadorId, 'id_donador'),
            ],
            'representante_legal' => ['nullable', 'string', 'max:200'],
            'giro_comercial' => ['nullable', 'string', 'max:100'],
            'nombre_grupo' => [Rule::requiredIf($tipo === 'grupo'), 'nullable', 'string', 'max:150'],
            'representante' => ['nullable', 'string', 'max:200'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('donadores', 'email')->ignore($donadorId, 'id_donador'),
            ],
            'telefono' => ['nullable', 'string', 'max:15', 'regex:/^(?=(?:\D*\d){10}\D*$)[0-9()+\-\s]+$/'],
            'puntos_acumulados' => ['nullable', 'integer', 'min:0'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_persona.required' => 'Selecciona el tipo de donador.',
            'tipo_persona.in' => 'El tipo de donador seleccionado no es válido.',
            'nombre.required' => 'El nombre es obligatorio para una persona física.',
            'apellido.required' => 'El apellido es obligatorio para una persona física.',
            'curp.regex' => 'La CURP debe tener 18 caracteres y un formato válido.',
            'curp.unique' => 'La CURP ya está registrada.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
            'razon_social.required' => 'La razón social es obligatoria para una persona moral.',
            'rfc.regex' => 'El RFC debe tener 12 caracteres y un formato válido.',
            'rfc.unique' => 'El RFC ya está registrado.',
            'nombre_grupo.required' => 'El nombre del grupo es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'telefono.regex' => 'El teléfono debe contener 10 dígitos y solo puede usar espacios, +, - o paréntesis.',
            'puntos_acumulados.integer' => 'Los puntos deben ser un número entero.',
            'puntos_acumulados.min' => 'Los puntos no pueden ser negativos.',
        ];
    }
}
