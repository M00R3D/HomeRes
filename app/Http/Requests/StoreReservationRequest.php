<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'usuario_id'   => 'nullable|exists:usuarios,id',
            'propiedad_id' => 'nullable|exists:propiedades,id',
            'check_in'     => 'required|date|after_or_equal:today',
            'check_out'    => 'required|date|after:check_in',
            'num_personas' => 'required|integer|min:1',
            'total'        => 'required|numeric|min:0',
            'estado'       => 'nullable|in:pendiente,confirmada,cancelada,completada',
            'nota'         => 'nullable|string|max:500',
            'estado_pago'  => 'nullable|in:pendiente,pagado,cancelado',
        ];
    }

    public function messages(): array
    {
        return [
            'check_in.after_or_equal' => 'La fecha de entrada debe ser hoy o posterior.',
            'check_out.after'         => 'La fecha de salida debe ser posterior a la entrada.',
            'num_personas.min'        => 'Debe haber al menos 1 persona.',
            'total.min'               => 'El total no puede ser negativo.',
        ];
    }
}
