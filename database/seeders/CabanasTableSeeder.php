<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CabanasTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('cabanas')->insert([
            ['codigo' => 'C001', 'nombre' => 'Cabaña Encino', 'descripcion' => 'Cabaña con vista al lago', 'capacidad' => 4, 'precio_noche' => 1200.00, 'ubicacion' => 'Zona A', 'servicios' => 'Wifi, Jacuzzi, Cocina', 'estado' => 'disponible', 'created_at' => now()],
            ['codigo' => 'C002', 'nombre' => 'Cabaña Roble', 'descripcion' => 'Ideal para parejas', 'capacidad' => 2, 'precio_noche' => 900.00, 'ubicacion' => 'Zona B', 'servicios' => 'Wifi, Chimenea', 'estado' => 'disponible', 'created_at' => now()],
            ['codigo' => 'C003', 'nombre' => 'Cabaña Pino', 'descripcion' => 'Capacidad familiar', 'capacidad' => 6, 'precio_noche' => 1800.00, 'ubicacion' => 'Zona C', 'servicios' => 'Wifi, Cocina, TV', 'estado' => 'mantenimiento', 'created_at' => now()],
        ]);
    }
}