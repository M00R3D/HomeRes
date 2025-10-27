<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropiedadesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('propiedades')->insert([
            [
                'tipo' => 'casa',
                'codigo' => 'M010',
                'nombre' => 'Casa Jason',
                'descripcion' => 'Propiedad de ejemplo',
                'capacidad' => 1,
                'precio_noche' => 1212.00,
                'ubicacion' => 'Ubicación de prueba',
                'servicios' => 'wifi',
                'estado' => 'disponible',
                'ruta_img' => '1/1.webp',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}