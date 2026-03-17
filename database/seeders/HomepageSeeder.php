<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HomepageSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('homepage')->updateOrInsert(
            ['id' => 1],
            [
                'banner_image' => 'uploads/banner.jpg',
                'image_folder' => 'uploads/homepage',
                'ubicacion' => 'Valle de las Flores',
                'eslogan' => 'Escápate y descansa',
                'nombre_empresa' => 'HomeRes Demo',
                'meta' => json_encode([
                    'settings' => [
                        'show_reservations' => true,
                        'show_properties' => true,
                    ],
                    'blocks' => [
                        [
                            'id' => 'hero-default',
                            'type' => 'hero',
                            'enabled' => true,
                            'title' => 'Escápate y descansa',
                            'subtitle' => 'Hospedajes con personalidad, reservas simples y una portada que puedes editar en vivo.',
                            'image' => 'uploads/banner.jpg',
                            'height' => 'lg',
                            'overlay' => 45,
                            'primary_label' => 'Explorar propiedades',
                            'primary_url' => '/propiedades',
                            'secondary_label' => 'Mis reservaciones',
                            'secondary_url' => '/reservaciones',
                            'align' => 'left',
                        ],
                        [
                            'id' => 'faq-default',
                            'type' => 'faq',
                            'enabled' => true,
                            'title' => 'Preguntas frecuentes',
                            'items' => [
                                ['question' => '¿Puedo reservar en línea?', 'answer' => 'Sí, cada propiedad tiene acceso directo a su flujo de reservación.'],
                                ['question' => '¿Puedo pagar con tarjeta?', 'answer' => 'Sí, si tu cuenta tiene una tarjeta asignada podrás usarla en el flujo de pago.'],
                            ],
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}