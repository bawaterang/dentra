<?php

namespace App\Livewire\Layouts;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationMenu extends Component
{
    public $notifications = [];
    public $unreadCount = 0;

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        $user = Auth::user();
        if ($user) {
            $this->notifications = $user->notifications()->take(10)->get();
            $this->unreadCount = $user->unreadNotifications()->count();
        }
    }

    public function getListeners()
    {
        if (!Auth::check()) return [];
        
        return [
            "echo-private:App.Models.User." . Auth::id() . ",.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated" => 'notifyNew',
        ];
    }

    public function notifyNew()
    {
        // Reload notifications when a new broadcast is received
        $this->loadNotifications();
    }

    public function markAsRead($id)
    {
        $user = Auth::user();
        if ($user) {
            $notification = $user->notifications()->where('id', $id)->first();
            if ($notification) {
                $notification->markAsRead();
                $this->loadNotifications();
            }
        }
    }

    public function markAllAsRead()
    {
        $user = Auth::user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
            $this->loadNotifications();
        }
    }

    public function render()
    {
        return view('livewire.layouts.notification-menu');
    }
}
