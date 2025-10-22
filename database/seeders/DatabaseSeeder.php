<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UsuariosTableSeeder::class,
            CabanasTableSeeder::class,
            ReservacionesTableSeeder::class,
            PagosTableSeeder::class,
            NotificacionesTableSeeder::class,
            ComentariosTableSeeder::class,
        ]);
    }
}
