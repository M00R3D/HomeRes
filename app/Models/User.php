<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'password',
        'rol',
        'area',
        'id_tarjeta',
        'intentos_cvv',
        'bloqueo_tarjetas',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'intentos_cvv' => 'int',
        'bloqueo_tarjetas' => 'boolean',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'usuario_id', 'id');
    }

    public function comentarios()
    {
        return $this->hasMany(Comentario::class, 'usuario_id', 'id');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notification::class, 'usuario_id', 'id');
    }

    public function tarjeta()
    {
        return $this->belongsTo(\App\Models\TarjetaSimulada::class, 'id_tarjeta', 'id');
    }
}
