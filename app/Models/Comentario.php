<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comentario extends Model
{
    use HasFactory;

    protected $table = 'comentarios';
    protected $primaryKey = 'id';
    protected $fillable = [
        'reservacion_id',
        'usuario_id',
        'calificacion',
        'comentario',
        'fecha_creacion'
    ];
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'id');
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservacion_id', 'id');
    }
}