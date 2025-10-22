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

    // la tabla tiene created_at y updated_at (migrations)
    public $timestamps = true;
    // const UPDATED_AT = null; // eliminado

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'password',
        'rol',
        'area'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'usuario_id', 'id');
    }

    public function comentarios()
    {
        return $this->hasMany(Comentario::class, 'usuario_id', 'id');
    }

    // Renombrada para evitar conflicto con Notifiable::notifications()
    public function notificaciones()
    {
        return $this->hasMany(Notification::class, 'usuario_id', 'id');
    }
}
