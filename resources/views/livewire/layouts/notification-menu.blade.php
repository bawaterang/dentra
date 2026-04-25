<div class="dropdown" x-data="{ open: false }" @click.outside="open = false">
    {{-- Trigger Button --}}
    <button class="topbar-icon-btn" title="Notifikasi" @click="open = !open">
        @if($unreadCount > 0)
            <span class="topbar-badge ping"></span>
            <span class="topbar-badge"></span>
        @endif
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
        </svg>
    </button>

    {{-- Dropdown Menu --}}
    <div class="dropdown-menu notif-menu" x-show="open" x-cloak x-transition style="display:none;">
        <div class="notif-header">
            <h6>Notifikasi</h6>
            @if($unreadCount > 0)
                <span class="notif-badge-count">{{ $unreadCount }} Baru</span>
            @endif
        </div>
        
        <div class="notif-body">
            @forelse($notifications as $notif)
                @php
                    $isUnread = is_null($notif->read_at);
                    $data = $notif->data;
                    $type = $data['type'] ?? 'info';
                    $bgClass = match($type) {
                        'success' => 'bg-success-soft',
                        'warning' => 'bg-warning-soft',
                        'primary' => 'bg-primary-soft',
                        default   => 'bg-info-soft',
                    };
                @endphp
                <a href="#" wire:click.prevent="markAsRead('{{ $notif->id }}')" class="notif-item {{ $isUnread ? 'unread' : '' }}">
                    <div class="notif-icon {{ $bgClass }}">
                        <i class="{{ $data['icon'] ?? 'ri-notification-3-line' }}"></i>
                    </div>
                    <div class="notif-content">
                        <h6>{{ $data['title'] ?? 'Notifikasi' }}</h6>
                        <p>{{ $data['message'] ?? '' }}</p>
                        <span class="notif-time"><i class="ri-time-line"></i> {{ $notif->created_at->diffForHumans() }}</span>
                    </div>
                </a>
            @empty
                <div style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 13px;">
                    <i class="ri-notification-off-line" style="font-size: 32px; display: block; margin-bottom: 8px; color: var(--border-color);"></i>
                    Tidak ada notifikasi baru
                </div>
            @endforelse
        </div>
        
        <div class="notif-footer">
            <a href="#" wire:click.prevent="markAllAsRead">Tandai semua dibaca <i class="ri-check-double-line"></i></a>
        </div>
    </div>
</div>
