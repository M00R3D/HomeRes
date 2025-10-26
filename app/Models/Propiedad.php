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

    public $timestamps = true;

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'propiedad_id', 'id');
    }

}