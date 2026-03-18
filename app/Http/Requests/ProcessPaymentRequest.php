<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'reservacion_id' => 'required|exists:reservaciones,id',
            'tarjeta_id'     => 'nullable|exists:tarjetas_simuladas,id',
            'usuario_id'     => 'required|exists:usuarios,id',
            'monto'          => 'required|numeric|min:0.01',
            'metodo_pago'    => 'required|string|in:tarjeta,efectivo',
            'cvv'            => 'nullable|string|max:4',
        ];
    }

    public function messages(): array
    {
        return [
            'reservacion_id.required' => 'Debes seleccionar una reservación.',
            'monto.min'               => 'El monto debe ser mayor a cero.',
            'metodo_pago.in'          => 'El método de pago no es válido.',
        ];
    }
}
