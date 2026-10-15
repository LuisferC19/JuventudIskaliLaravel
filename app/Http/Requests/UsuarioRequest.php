<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UsuarioRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $password = $this->input('password');
        $confirmation = $this->input('confirmar_password');

        $this->merge([
            'nombre' => trim((string) $this->input('nombre')),
            'apellido' => trim((string) $this->input('apellido')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'password' => is_string($password) && trim($password) !== '' ? trim($password) : null,
            'confirmar_password' => is_string($confirmation) && trim($confirmation) !== '' ? trim($confirmation) : null,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $usuarioId = $this->route('usuario');
        $esCreacion = $this->isMethod('post');

        return [
            'nombre' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[\pL\pM ]+$/u'],
            'apellido' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[\pL\pM ]+$/u'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('usuarios', 'email')->ignore($usuarioId, 'id_usuario'),
            ],
            'id_rol' => ['required', 'integer', 'exists:roles,id_rol'],
            'password' => [$esCreacion ? 'required' : 'nullable', 'string', 'min:6'],
            'confirmar_password' => [
                $esCreacion ? 'required' : 'required_with:password',
                'same:password',
            ],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no puede superar 100 caracteres.',
            'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
            'apellido.required' => 'El apellido es obligatorio.',
            'apellido.min' => 'El apellido debe tener al menos 2 caracteres.',
            'apellido.max' => 'El apellido no puede superar 100 caracteres.',
            'apellido.regex' => 'El apellido solo puede contener letras y espacios.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.max' => 'El correo no puede superar 150 caracteres.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'id_rol.required' => 'Debes seleccionar un rol.',
            'id_rol.exists' => 'El rol seleccionado no existe.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'confirmar_password.required' => 'Confirma la contraseña.',
            'confirmar_password.required_with' => 'Confirma la nueva contraseña.',
            'confirmar_password.same' => 'Las contraseñas no coinciden.',
        ];
    }
}
