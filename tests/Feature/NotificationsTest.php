<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewMessageNotification;

class NotificationsTest extends TestCase
{
    public function test_notifications_count_endpoint()
    {
        $user = User::factory()->create();
        Notification::send($user, new NewMessageNotification(['title'=>'t','body'=>'b']));

        $this->actingAs($user)
            ->getJson('/notifications/count')
            ->assertStatus(200)
            ->assertJsonStructure(['unread_count']);
    }
}
