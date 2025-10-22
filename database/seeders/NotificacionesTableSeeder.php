<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificacionesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('notificaciones')->insert([
            ['usuario_id' => 3, 'reservacion_id' => 1, 'propiedad_id' => null, 'estado' => 'abierta', 'tipo' => 'info', 'descripcion' => 'Nueva reservación creada', 'fecha_creacion' => now()],
            ['usuario_id' => 3, 'reservacion_id' => 2, 'propiedad_id' => null, 'estado' => 'vista', 'tipo' => 'confirmacion', 'descripcion' => 'Reservación confirmada', 'fecha_creacion' => now()],
        ]);
    }
}