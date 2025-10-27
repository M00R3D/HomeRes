<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HomepageSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('homepage')->insert([
            [
                'banner_image' => 'uploads/banner-default.jpg',
                'image_folder' => 'uploads/homepage',
                'ubicacion' => 'Valle de las Flores',
                'eslogan' => 'Escápate y descansa',
                'nombre_empresa' => 'HomeRes Demo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}