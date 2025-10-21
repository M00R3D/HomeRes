<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'pagos';
    protected $primaryKey = 'id';
    protected $fillable = [
        'reservacion_id',
        'monto',
        'metodo_pago',
        'estado',
        'fecha_pago'
    ];
    public $timestamps = false;

    public function reservation() { return $this->belongsTo(Reservation::class, 'reservacion_id', 'id'); }
}