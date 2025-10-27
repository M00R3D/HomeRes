<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PagosTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('pagos')->insert([
            [
                'reservacion_id' => 1,
                'monto' => 2400.00,
                'metodo_pago' => 'tarjeta',
                'estado' => 'pendiente',
                'fecha_pago' => now(),
            ],
            [
                'reservacion_id' => 2,
                'monto' => 1800.00,
                'metodo_pago' => 'efectivo',
                'estado' => 'pagado',
                'fecha_pago' => now(),
            ],
        ]);
    }
}