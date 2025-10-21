<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';
    protected $primaryKey = 'id';
    protected $fillable = [
        'usuario_id',
        'reservacion_id',
        'propiedad_id',
        'estado',
        'tipo',
        'descripcion',
        'fecha_creacion',
        'fecha_visto'
    ];

    // la tabla usa campos de timestamp manuales
    public $timestamps = false;

    public function user() { return $this->belongsTo(User::class, 'usuario_id', 'id'); }
    public function reservation() { return $this->belongsTo(Reservation::class, 'reservacion_id', 'id'); }
    // si tienes modelo Propiedad crea relación similar:
    // public function propiedad() { return $this->belongsTo(Propiedad::class, 'propiedad_id', 'id'); }
}