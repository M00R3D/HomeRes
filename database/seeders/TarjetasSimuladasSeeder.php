<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TarjetasSimuladasSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tarjetas_simuladas')->insert([
            [
                'numero_tarjeta' => '4111111111111111',
                'nombre' => 'Test Card One',
                'expiracion' => '12/28',
                'cvv' => '123',
                'saldo' => 5000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'numero_tarjeta' => '5500000000000004',
                'nombre' => 'Test Card Two',
                'expiracion' => '08/27',
                'cvv' => '321',
                'saldo' => 1500.50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}