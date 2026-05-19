@php
    $messages = $messages ?? [];
    $rolePalette = [
        'customer' => ['bg' => '#fff7ed', 'border' => '#fdba74', 'text' => '#9a3412'],
        'restaurant' => ['bg' => '#ecfdf5', 'border' => '#86efac', 'text' => '#007836'],
        'driver' => ['bg' => '#eef2ff', 'border' => '#a5b4fc', 'text' => '#3730a3'],
        'admin' => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#0f172a'],
        'system' => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#475569'],
    ];
@endphp

<div class="order-chat-message-list">
    @forelse($messages as $message)
        @php
            $palette = $rolePalette[$message['role']] ?? $rolePalette['system'];
        @endphp
        <div class="order-chat-message {{ $message['mine'] ? 'is-mine' : 'is-other' }} order-chat-message--{{ $message['role'] }}"
             style="background:{{ $palette['bg'] }}; border-color:{{ $palette['border'] }}; color:{{ $palette['text'] }};">
            <div class="order-chat-message__meta">
                <span class="order-chat-message__label">{{ $message['label'] }}</span>
                <span class="order-chat-message__time">{{ $message['time'] }}</span>
            </div>
            <div class="order-chat-message__body">{{ $message['body'] }}</div>
            @if(!empty($message['seen_label']))
                <div class="order-chat-message__seen">{{ $message['seen_label'] }}</div>
            @endif
        </div>
    @empty
        <div class="order-chat-empty-state">
            Aucun message pour le moment. Ouvrez la conversation pour synchroniser les échanges.
        </div>
    @endforelse
</div>
