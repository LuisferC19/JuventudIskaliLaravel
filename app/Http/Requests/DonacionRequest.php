<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
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

        if ($this->isMethod('post')) {
            return [
                'tipo' => ['required', Rule::in(['especie', 'economica'])],
                'id_donador' => ['required', 'integer', Rule::exists('donadores', 'id_donador')->where('activo', true)],
                'id_campana' => ['required', 'integer', Rule::exists('campanas', 'id_campana')->where('estado', 'activa')],
                'fecha_recepcion' => ['required', 'date_format:Y-m-d'],
                'evidencia_url' => ['nullable', 'url', 'max:255'],
                'id_tipo_bien' => ['required_if:tipo,especie', 'nullable', 'integer', Rule::exists('tipos_bien', 'id_tipo_bien')->where('activo', true)],
                'descripcion' => ['nullable', 'string'],
                'cantidad' => ['required_if:tipo,especie', 'nullable', 'numeric', 'gt:0', 'max:9999999999.99'],
                'monto' => ['required_if:tipo,economica', 'nullable', 'numeric', 'gt:0', 'max:9999999999.99'],
                'moneda' => ['nullable', 'regex:/^[A-Z]{3}$/'],
                'metodo_pago' => ['required_if:tipo,economica', 'nullable', Rule::in(['efectivo', 'transferencia', 'cheque', 'otro'])],
                'referencia_pago' => ['nullable', 'string', 'max:100'],
            ];
        }

        $idDonacion = $this->route('donacion');
        $esEspecie = DB::table('donaciones_especie')->where('id_donacion', $idDonacion)->exists();
        $rules = [
            'id_campana' => ['required', 'integer', Rule::exists('campanas', 'id_campana')],
            'fecha_recepcion' => ['required', 'date_format:Y-m-d'],
            'evidencia_url' => ['nullable', 'url', 'max:255'],
        ];

        if ($esEspecie) {
            $rules += [
                'id_tipo_bien' => ['required', 'integer', Rule::exists('tipos_bien', 'id_tipo_bien')->where('activo', true)],
                'descripcion' => ['nullable', 'string'],
                'cantidad' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            ];
        } else {
            $rules += [
                'monto' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
                'moneda' => ['nullable', 'regex:/^[A-Z]{3}$/'],
                'metodo_pago' => ['required', Rule::in(['efectivo', 'transferencia', 'cheque', 'otro'])],
                'referencia_pago' => ['nullable', 'string', 'max:100'],
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'Selecciona el tipo de donación.',
            'tipo.in' => 'El tipo de donación seleccionado no es válido.',
            'id_donador.required' => 'Selecciona un donador activo.',
            'id_donador.exists' => 'El donador seleccionado no está activo.',
            'id_campana.required' => 'Selecciona una campaña.',
            'id_campana.exists' => 'La campaña seleccionada no está disponible.',
            'fecha_recepcion.required' => 'La fecha de recepción es obligatoria.',
            'fecha_recepcion.date_format' => 'La fecha de recepción no tiene un formato válido.',
            'evidencia_url.url' => 'La URL de evidencia no es válida.',
            'id_tipo_bien.required_if' => 'Selecciona el tipo de bien donado.',
            'id_tipo_bien.exists' => 'El tipo de bien seleccionado no está activo.',
            'cantidad.required_if' => 'La cantidad de bienes es obligatoria.',
            'cantidad.numeric' => 'La cantidad debe ser un número.',
            'cantidad.gt' => 'La cantidad debe ser mayor que cero.',
            'monto.required_if' => 'El monto de la donación es obligatorio.',
            'monto.numeric' => 'El monto debe ser un número.',
            'monto.gt' => 'El monto debe ser mayor que cero.',
            'moneda.regex' => 'La moneda debe ser un código de tres letras mayúsculas.',
            'metodo_pago.required_if' => 'Selecciona el método de pago.',
            'metodo_pago.in' => 'El método de pago seleccionado no es válido.',
            'referencia_pago.max' => 'La referencia de pago no puede superar los 100 caracteres.',
            'estado.required' => 'Selecciona el estado de la donación.',
            'estado.in' => 'El estado solicitado no es válido.',
        ];
    }
}
