<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestablecerContrasenaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/\d/'],
            'password_confirm' => ['required', 'same:password'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.regex' => 'La contraseña debe incluir una mayúscula y un número.',
            'password_confirm.required' => 'Confirma la nueva contraseña.',
            'password_confirm.same' => 'La confirmación no coincide con la contraseña.',
        ];
    }
}
