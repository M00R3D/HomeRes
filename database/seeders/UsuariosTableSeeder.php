<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsuariosTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('usuarios')->insert([
            ['nombre' => 'Juan', 'apellido' => 'Pérez', 'email' => 'juan@correo.com', 'password' => bcrypt('secret'), 'rol' => 'admin', 'area' => 'Administración', 'created_at' => now()],
            ['nombre' => 'Ana', 'apellido' => 'García', 'email' => 'ana@correo.com', 'password' => bcrypt('secret'), 'rol' => 'recepcionista', 'area' => 'Recepción', 'created_at' => now()],
            ['nombre' => 'Job', 'apellido' => 'Moore', 'email' => 'jobmurdan@hotmail.com', 'password' => bcrypt('secret'), 'rol' => 'cliente', 'area' => 'Cliente', 'created_at' => now()],
            ['nombre' => 'Job', 'apellido' => 'Moore', 'email' => 'a@mail.com', 'password' => bcrypt('123123'), 'rol' => 'admin', 'area' => 'Cliente', 'created_at' => now()],
            ['nombre' => 'Admin', 'apellido' => 'Test', 'email' => 'aaaaa@mail.com', 'password' => bcrypt('123123123'), 'rol' => 'admin', 'area' => 'Cliente', 'created_at' => now()],
        ]);
    }
}