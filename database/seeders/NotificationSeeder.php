<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewMessageNotification;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        foreach($users as $u){
            for($i=1;$i<=3;$i++){
                Notification::send($u, new NewMessageNotification([
                    'title' => "Mensaje de prueba #$i",
                    'body' => 'Este es un mensaje de prueba para la campana',
                    'url' => '/reservaciones',
                ]));
            }
        }
    }
}
