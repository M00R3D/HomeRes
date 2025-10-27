<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Homepage extends Model
{
    use HasFactory;

    protected $table = 'homepage';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'banner_image',
        'image_folder',
        'ubicacion',
        'eslogan',
        'nombre_empresa',
    ];
}