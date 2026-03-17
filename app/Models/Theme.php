<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    use HasFactory;

    protected $table = 'themes';
    protected $fillable = [
        'name','btn_primary','btn_alt','bg','sidebar_bg','sidebar_text',
        'gradient_start','gradient_end','gradient_angle','animated_gradient',
        'animation_speed','font_size','button_variants','meta'
    ];

    protected $casts = [
        'animated_gradient' => 'boolean',
        'animation_speed' => 'float',
        'font_size' => 'integer',
        'button_variants' => 'array',
        'meta' => 'array'
    ];
}
