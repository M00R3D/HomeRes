<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Reservation extends Model
{
    use HasFactory;

    protected $table = 'reservaciones';
    protected $primaryKey = 'id';
    protected $fillable = [
        'usuario_id',
        'propiedad_id',
        'check_in',
        'check_out',
        'num_personas',
        'total',
        'estado',
        'nota',
        'estado_pago',
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

    /**
     * Determine if the reservation dates are expired (check-out in the past).
     */
    public function isExpired(): bool
    {
        if (empty($this->check_out)) return false;
        try {
            $co = Carbon::parse($this->check_out)->endOfDay();
            return $co->lt(Carbon::now());
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Determine if the reservation has been paid (any related payment marked as pagado,
     * or the reserva's estado_pago field equals 'pagado').
     */
    public function isPaid(): bool
    {
        try {
            if ($this->payments()->where('estado', 'pagado')->exists()) return true;
        } catch (\Throwable $e) {
            // ignore
        }
        return strtolower(trim((string)($this->estado_pago ?? ''))) === 'pagado';
    }
}
