<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class RealtimeNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    private array $data;

    /**
     * Create a new notification instance.
     *
     * @param string $title Judul Notifikasi
     * @param string $message Pesan Notifikasi
     * @param string $type Jenis (success, info, warning, primary)
     * @param string $icon Ikon (e.g. ri-user-add-line)
     * @param string|null $url Link tautan jika diklik
     */
    public function __construct(
        string $title,
        string $message,
        string $type = 'info',
        string $icon = 'ri-notification-3-line',
        ?string $url = null
    ) {
        $this->data = [
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'icon' => $icon,
            'url' => $url,
            'time' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Data to be stored into `notifications` table.
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->data;
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'data' => $this->data,
            'id' => $this->id,
            'read_at' => null,
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Optional: which channel to broadcast on
     * Here we just rely on the default User private-channel `App.Models.User.{id}`
     * Since the user requested broadcasting to EVERY authenticated user, we can broadcast on a public channel,
     * or we can just broadcast to every User model. Given that notifications are model context bound in DB, 
     * broadcasting to specific users in DB is easier.
     * I will create a broadcastOn method if we want a global channel, but sending to all Users iteratively works too.
     */
}
