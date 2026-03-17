<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class LogCreatedNotification extends Notification
{
    use Queueable;

    protected $log;

    public function __construct(array $payload)
    {
        $this->log = $payload;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->log['tipo'] ?? 'Registro',
            'body' => $this->log['mensaje'] ?? '',
            'link' => $this->log['link'] ?? ($this->log['url'] ?? null),
            'meta' => [
                'usuario_id' => $this->log['usuario_id'] ?? null,
                'referencia_tipo' => $this->log['referencia_tipo'] ?? null,
                'referencia_id' => $this->log['referencia_id'] ?? null,
            ],
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
