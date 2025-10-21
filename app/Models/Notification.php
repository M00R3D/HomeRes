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
        'id_admin',
        'id_usuario',
        'estado',
        'tipo',
        'descripcion',
        'fecha_creacion',
        'fecha_visto',
        'ruta'
    ];

    // en el dump hay fecha_creacion como timestamp
    public $timestamps = false;

    public function user() { return $this->belongsTo(User::class, 'id_usuario', 'id'); }
}