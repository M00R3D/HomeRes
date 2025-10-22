<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Propiedad extends Model
{
    use HasFactory;

    protected $table = 'propiedades';
    protected $primaryKey = 'id';
    protected $fillable = [
        'tipo',
        'codigo',
        'nombre',
        'descripcion',
        'capacidad',
        'precio_noche',
        'ubicacion',
        'servicios',
        'estado',
        'ruta_img',
        'created_at'
    ];

    // la tabla tiene created_at y updated_at (migrations)
    public $timestamps = true;
    // const UPDATED_AT = null; // eliminado

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'propiedad_id', 'id');
    }

    // NOTA: la tabla 'comentarios' de las migrations no contiene 'propiedad_id'.
    // Si en el futuro añades propiedad_id a comentarios puedes reactivar la relación.
    // public function comentarios() { return $this->hasMany(Comentario::class, 'propiedad_id', 'id'); }
}