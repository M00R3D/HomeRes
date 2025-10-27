<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $table = 'reservaciones';
    protected $primaryKey = 'id';
    protected $fillable = [
        'usuario_id',
        'cabana_id',
        'check_in',
        'check_out',
        'num_personas',
        'total',
        'estado',
        'nota'
    ];

    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'id');
    }

    public function propiedad()
    {
        return $this->belongsTo(\App\Models\Propiedad::class, 'propiedad_id', 'id');
    }
    public function payments() { return $this->hasMany(Payment::class, 'reservacion_id', 'id'); }
    public function chats() { return $this->hasMany(Chat::class); }
    public function histories() { return $this->hasMany(ReservationHistory::class); }
}
