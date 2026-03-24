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
        // Works with both Route::resource ({propiedade}) and explicit {id} routes
        $param = $this->route('propiedade') ?? $this->route('propiedad') ?? $this->route('id');
        $id = ($param instanceof \App\Models\Propiedad) ? $param->id : $param;

        return [
            'tipo'         => 'required|in:cabaña,casa,departamento',
            'codigo'       => "required|string|max:50|unique:propiedades,codigo,{$id}",
            'nombre'       => 'required|string|max:255',
            'descripcion'  => 'required|string',
            'capacidad'    => 'required|integer|min:1',
            'precio_noche' => 'required|numeric|min:0',
            'ubicacion'    => 'required|string|max:255',
            'servicios'    => 'nullable|string',
            'estado'       => 'required|in:disponible,ocupada,mantenimiento',
            'ruta_img'     => 'nullable|string|max:500',
        ];
    }
}
