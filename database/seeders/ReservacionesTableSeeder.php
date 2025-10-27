<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReservacionesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('reservaciones')->insert([
            ['usuario_id' => 3, 'propiedad_id' => 1, 'check_in' => '2025-11-01', 'check_out' => '2025-11-03', 'num_personas' => 2, 'total' => 2400.00, 'estado' => 'pendiente', 'nota' => 'Primera reserva de prueba', 'created_at' => now()],
            ['usuario_id' => 3, 'propiedad_id' => 1, 'check_in' => '2025-11-10', 'check_out' => '2025-11-12', 'num_personas' => 2, 'total' => 1800.00, 'estado' => 'confirmada', 'nota' => 'Cliente recurrente', 'created_at' => now()],
        ]);
    }
}