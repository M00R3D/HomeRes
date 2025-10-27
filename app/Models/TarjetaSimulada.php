<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarjetaSimulada extends Model
{
    use HasFactory;

    protected $table = 'tarjetas_simuladas';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'numero_tarjeta',
        'nombre',
        'expiracion',
        'cvv',
        'saldo',
    ];

    public function assignedUser()
    {
        return $this->hasOne(\App\Models\User::class, 'id_tarjeta', 'id');
    }
}