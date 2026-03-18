<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropiedadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->rol ?? '') === 'admin';
    }

    public function rules(): array
    {
        // Works with both Route::resource ({propiedad}) and explicit {id} routes
        $id = $this->route('propiedad') ?? $this->route('id');

        return [
            'tipo'         => 'required|in:cabaña,casa,departamento',
            'codigo'       => "required|string|max:50|unique:propiedades,codigo,{$id}",
            'nombre'       => 'required|string|max:255',
            'descripcion'  => 'nullable|string',
            'capacidad'    => 'required|integer|min:1',
            'precio_noche' => 'required|numeric|min:0',
            'ubicacion'    => 'nullable|string|max:255',
            'servicios'    => 'nullable|string',
            'estado'       => 'nullable|in:disponible,ocupada,mantenimiento',
            'ruta_img'     => 'nullable|string|max:500',
        ];
    }
}
