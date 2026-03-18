<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Theme;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $presets = [
            ['name' => 'Light',
                'btn_primary' => '#2563eb',
                'btn_alt' => '#06b6d4',
                'bg' => '#ffffff',
                'sidebar_bg' => '#f8fafc',
                'sidebar_text' => '#0f172a',
                'gradient_start' => '#2563eb',
                'gradient_end' => '#06b6d4',
                'gradient_angle' => 90,
                'animated_gradient' => false,
                'animation_speed' => 6,
                'font_size' => 16,
                'button_variants' => [
                    'primary' => ['bg' => '#2563eb', 'color' => '#ffffff'],
                    'alt' => ['bg' => '#06b6d4', 'color' => '#ffffff'],
                    'danger' => ['bg' => '#ef4444', 'color' => '#ffffff'],
                    'ghost' => ['bg' => 'transparent', 'color' => '#2563eb', 'border' => '#e6eefb'],
                    'link' => ['bg' => 'transparent', 'color' => '#2563eb'],
                    'modal-confirm' => ['bg' => '#2563eb', 'color' => '#ffffff'],
                    'modal-cancel' => ['bg' => '#e5e7eb', 'color' => '#111827'],
                    'edit' => ['bg' => '#06b6d4', 'color' => '#ffffff'],
                    'new' => ['bg' => '#10b981', 'color' => '#ffffff'],
                ],
                'meta' => ['topbar' => ['bg' => '#ffffff', 'text' => '#0f172a']],
                'meta' => [
                    'topbar' => ['bg' => '#ffffff', 'text' => '#0f172a'],
                    'payment' => [
                        'pagado' => ['bg' => '#10b981', 'text' => '#ffffff'],
                        'pendiente' => ['bg' => '#f59e0b', 'text' => '#ffffff'],
                        'fallido' => ['bg' => '#ef4444', 'text' => '#ffffff'],
                        'parcial' => ['bg' => '#6366f1', 'text' => '#ffffff'],
                    ],
                    'price' => [
                        'confirmada' => '#065f46',
                        'pendiente' => '#92400e',
                        'cancelada' => '#7f1d1d',
                        'default' => '#374151',
                    ],
                ],
            ],
            ['name' => 'Dark',
                'btn_primary' => '#7c3aed',
                'btn_alt' => '#fb923c',
                'bg' => '#0b1220',
                'sidebar_bg' => '#071029',
                'sidebar_text' => '#f8fafc',
                'gradient_start' => '#7c3aed',
                'gradient_end' => '#fb923c',
                'gradient_angle' => 90,
                'animated_gradient' => false,
                'animation_speed' => 6,
                'font_size' => 16,
                'button_variants' => [
                    'primary' => ['bg' => '#7c3aed', 'color' => '#ffffff'],
                    'alt' => ['bg' => '#111827', 'color' => '#f8fafc'],
                    'danger' => ['bg' => '#ef4444', 'color' => '#ffffff'],
                    'ghost' => ['bg' => 'transparent', 'color' => '#7c3aed'],
                    'link' => ['bg' => 'transparent', 'color' => '#7c3aed'],
                    'modal-confirm' => ['bg' => '#7c3aed', 'color' => '#ffffff'],
                    'modal-cancel' => ['bg' => '#0f172a', 'color' => '#f8fafc'],
                    'edit' => ['bg' => '#06b6d4', 'color' => '#ffffff'],
                    'new' => ['bg' => '#10b981', 'color' => '#ffffff'],
                ],
                'meta' => [
                    'topbar' => ['bg' => '#071029', 'text' => '#f8fafc'],
                    'payment' => [
                        'pagado' => ['bg' => '#059669', 'text' => '#ffffff'],
                        'pendiente' => ['bg' => '#f97316', 'text' => '#ffffff'],
                        'fallido' => ['bg' => '#dc2626', 'text' => '#ffffff'],
                        'parcial' => ['bg' => '#06b6d4', 'text' => '#ffffff'],
                    ],
                    'price' => [
                        'confirmada' => '#9ae6b4',
                        'pendiente' => '#fb923c',
                        'cancelada' => '#fb7185',
                        'default' => '#f8fafc',
                    ],
                ],
            ],
            ['name' => 'Sakura',
                'btn_primary' => '#f9a8d4',
                'btn_alt' => '#ffd7b5',
                'bg' => '#fff7fb',
                'sidebar_bg' => '#fffaf6',
                'sidebar_text' => '#0f172a',
                'gradient_start' => '#f9a8d4',
                'gradient_end' => '#ffd7b5',
                'gradient_angle' => 90,
                'animated_gradient' => false,
                'animation_speed' => 6,
                'font_size' => 16,
                'button_variants' => [
                    'primary' => ['bg' => '#f9a8d4', 'color' => '#111827'],
                    'alt' => ['bg' => '#ffd7b5', 'color' => '#111827'],
                    'danger' => ['bg' => '#fb7185', 'color' => '#111827'],
                    'ghost' => ['bg' => 'transparent', 'color' => '#f9a8d4'],
                    'link' => ['bg' => 'transparent', 'color' => '#f9a8d4'],
                    'modal-confirm' => ['bg' => '#f9a8d4', 'color' => '#111827'],
                    'modal-cancel' => ['bg' => '#fff4f7', 'color' => '#111827'],
                    'edit' => ['bg' => '#ffd7b5', 'color' => '#111827'],
                    'new' => ['bg' => '#fb7185', 'color' => '#ffffff'],
                ],
                'meta' => [
                    'topbar' => ['bg' => '#fff4f7', 'text' => '#0f172a'],
                    'payment' => [
                        'pagado' => ['bg' => '#10b981', 'text' => '#ffffff'],
                        'pendiente' => ['bg' => '#f59e0b', 'text' => '#ffffff'],
                        'fallido' => ['bg' => '#ef4444', 'text' => '#ffffff'],
                        'parcial' => ['bg' => '#6366f1', 'text' => '#ffffff'],
                    ],
                    'price' => [
                        'confirmada' => '#065f46',
                        'pendiente' => '#92400e',
                        'cancelada' => '#7f1d1d',
                        'default' => '#374151',
                    ],
                ],
            ],
            ['name' => 'Abstract',
                'btn_primary' => '#6d28d9',
                'btn_alt' => '#fb923c',
                'bg' => '#f5f3ff',
                'sidebar_bg' => '#fdf2f8',
                'sidebar_text' => '#0f172a',
                'gradient_start' => '#6d28d9',
                'gradient_end' => '#fb923c',
                'gradient_angle' => 45,
                'animated_gradient' => true,
                'animation_speed' => 8,
                'font_size' => 16,
                'button_variants' => [
                    'primary' => ['bg' => '#6d28d9', 'color' => '#ffffff'],
                    'alt' => ['bg' => '#fb923c', 'color' => '#111827'],
                    'danger' => ['bg' => '#ef4444', 'color' => '#ffffff'],
                    'ghost' => ['bg' => 'transparent', 'color' => '#6d28d9'],
                    'link' => ['bg' => 'transparent', 'color' => '#6d28d9'],
                    'modal-confirm' => ['bg' => '#6d28d9', 'color' => '#ffffff'],
                    'modal-cancel' => ['bg' => '#fdf2f8', 'color' => '#111827'],
                    'edit' => ['bg' => '#fb923c', 'color' => '#111827'],
                    'new' => ['bg' => '#6d28d9', 'color' => '#ffffff'],
                ],
                'meta' => [
                    'topbar' => ['bg' => '#f5f3ff', 'text' => '#0f172a'],
                    'payment' => [
                        'pagado' => ['bg' => '#059669', 'text' => '#ffffff'],
                        'pendiente' => ['bg' => '#f97316', 'text' => '#ffffff'],
                        'fallido' => ['bg' => '#ef4444', 'text' => '#ffffff'],
                        'parcial' => ['bg' => '#06b6d4', 'text' => '#ffffff'],
                    ],
                    'price' => [
                        'confirmada' => '#065f46',
                        'pendiente' => '#92400e',
                        'cancelada' => '#7f1d1d',
                        'default' => '#374151',
                    ],
                ],
            ],
        ];

        foreach ($presets as $i => $p) {
            // ensure contrast: if bg is dark, force text light for buttons where needed
            $p = array_merge([
                'font_size' => 16,
                'hover_animation' => 'none',
                'hover_animation_duration' => 0.18,
                'float_animation' => 'none',
                'float_animation_duration' => 6,
            ], $p);

            $slotId = $i + 1;
            Theme::updateOrCreate(['id' => $slotId], $p);
        }

        $custom = Theme::find(5);
        if (! $custom) {
            $base = Theme::find(1)?->toArray() ?? [
                'name' => 'custom',
                'btn_primary' => '#2563eb',
                'btn_alt' => '#06b6d4',
                'bg' => '#ffffff',
                'sidebar_bg' => '#f8fafc',
                'sidebar_text' => '#0f172a',
                'gradient_start' => '#2563eb',
                'gradient_end' => '#06b6d4',
                'gradient_angle' => 90,
                'animated_gradient' => false,
                'animation_speed' => 6,
                'font_size' => 16,
            ];

            unset($base['id'], $base['created_at'], $base['updated_at']);
            $base['name'] = 'custom';
            Theme::updateOrCreate(['id' => 5], $base);
        } elseif (strtolower((string) $custom->name) !== 'custom') {
            $custom->name = 'custom';
            $custom->save();
        }
    }
}
