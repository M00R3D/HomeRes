<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UsuariosTableSeeder::class,
            PropiedadesTableSeeder::class,
            CabanasTableSeeder::class,
            ReservacionesTableSeeder::class,
            PagosTableSeeder::class,
            TarjetasSimuladasSeeder::class,
            HomepageSeeder::class,
            NotificacionesTableSeeder::class,
            ComentariosTableSeeder::class,
        ]);
    }
}
