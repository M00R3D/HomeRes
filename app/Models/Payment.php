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
        'tarjeta_id',
        'monto',
        'metodo_pago',
        'estado',
        'codigo_qr',
        'codigo_qr_generado_en',
        'fecha_pago',
        'usuario_id',
    ];

    protected $casts = [
        'codigo_qr_generado_en' => 'datetime',
    ];

    public $timestamps = false;

    public function reservation() { return $this->belongsTo(Reservation::class, 'reservacion_id', 'id'); }
    public function tarjeta() { return $this->belongsTo(\App\Models\TarjetaSimulada::class, 'tarjeta_id', 'id'); }
}