<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $table = 'logs';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'tipo',
        'mensaje',
        'usuario_id',
        'referencia_id',
        'referencia_tipo',
    ];

    /**
     * Helper to create log entries easily.
     * Usage: \App\Models\Log::entry('pago', 'Pago realizado', auth()->id(), 'reservacion', $id);
     */
    public static function entry(string $tipo, string $mensaje, $usuarioId = null, $referenciaTipo = null, $referenciaId = null)
    {
        return self::create([
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'usuario_id' => $usuarioId ?? (auth()->id() ?? null),
            'referencia_tipo' => $referenciaTipo,
            'referencia_id' => $referenciaId,
        ]);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'usuario_id', 'id');
    }
}
