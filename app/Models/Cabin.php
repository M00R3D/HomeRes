<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cabin extends Model
{
    use HasFactory;

    protected $table = 'cabanas';
    protected $primaryKey = 'id';
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'capacidad',
        'precio_noche',
        'ubicacion',
        'servicios',
        'estado',
        'ruta_img'
    ];

    public $timestamps = true;

    public function images() { return $this->hasMany(CabinImage::class); }
    public function calendars() { return $this->hasMany(Calendar::class); }
    public function reservations() { return $this->hasMany(Reservation::class, 'cabana_id', 'id'); }
    public function reviews() { return $this->hasMany(Review::class); }
}