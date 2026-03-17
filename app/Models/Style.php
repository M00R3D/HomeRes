<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Style extends Model
{
    use HasFactory;

    protected $table = 'styles';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name','btn_primary','btn_alt','bg','sidebar_bg','sidebar_text','transparency','variant','exotic_animation','meta'
    ];

    protected $casts = [
        'meta' => 'array',
        'transparency' => 'integer'
    ];
}
