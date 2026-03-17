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
        'animation_speed','font_size','button_variants','meta',
        'bg_gradient_start','bg_gradient_end','bg_gradient_angle','bg_animated',
        'sidebar_gradient_start','sidebar_gradient_end','sidebar_gradient_angle','sidebar_animated',
        'hover_animation','hover_animation_duration','float_animation','float_animation_duration'
    ];

    protected $casts = [
        'animated_gradient' => 'boolean',
        'animation_speed' => 'float',
        'font_size' => 'integer',
        'button_variants' => 'array',
        'meta' => 'array'
    ];

    protected $attributes = [
        'hover_animation' => 'none',
        'float_animation' => 'none'
    ];
}
