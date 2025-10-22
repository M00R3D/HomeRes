<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComentariosTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('comentarios')->insert([
            ['reservacion_id' => 1, 'usuario_id' => 3, 'calificacion' => 5, 'comentario' => 'Excelente servicio y atención.', 'fecha_creacion' => now()],
            ['reservacion_id' => 2, 'usuario_id' => 3, 'calificacion' => 4, 'comentario' => 'Muy buena experiencia, pero faltó limpieza.', 'fecha_creacion' => now()],
        ]);
    }
}