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

    // la tabla tiene created_at pero no updated_at
    public $timestamps = true;
    const UPDATED_AT = null;

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'propiedad_id', 'id');
    }

    public function comentarios()
    {
        // comentarios están asociados a reservaciones, no directamente a propiedad.
        // Si guardas comentarios por propiedad adapta la FK.
        return $this->hasMany(Comentario::class, 'propiedad_id', 'id');
    }
}