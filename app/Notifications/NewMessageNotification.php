<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NewMessageNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $body;
    protected $url;
    protected $icon;
    protected $meta;

    public function __construct(array $payload)
    {
        $this->title = $payload['title'] ?? 'Nuevo mensaje';
        $this->body  = $payload['body'] ?? '';
        $this->url   = $payload['url'] ?? null;
        $this->icon  = $payload['icon'] ?? null;
        $this->meta  = $payload['meta'] ?? null;
    }

    public function via($notifiable)
    {
        try{
            $prefs = \DB::table('notification_preferences')->where('user_id', $notifiable->id)->first();
            // Always persist in DB so notifications exist even if user has in-app disabled
            $channels = ['database'];
            if($prefs && !empty($prefs->receive_push)){
                $channels[] = 'broadcast';
            }
            return $channels;
        }catch(\Throwable $e){
            return ['database','broadcast'];
        }
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'icon' => $this->icon,
            'url' => $this->url,
            'link' => $this->url,
            'metadata' => $this->meta,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
