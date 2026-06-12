@extends('frontend.layouts.app-modern')

@section('title', 'Mes notifications')

@section('style')
<style>
/* ── Notifications inbox ──────────────────────────────────────────────────── */
.ntf-page { max-width: 680px; margin: 0 auto; padding: 24px 16px 80px; }

.ntf-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.ntf-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--bd-text, #111827);
    margin: 0;
    flex: 1;
}
.ntf-unread-chip {
    background: #007836;
    color: #fff;
    font-size: .72rem;
    font-weight: 700;
    padding: 2px 9px;
    border-radius: 20px;
    line-height: 1.6;
}
.ntf-mark-all-btn {
    font-size: .8rem;
    color: #007836;
    background: none;
    border: 1px solid #007836;
    border-radius: 20px;
    padding: 4px 14px;
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s, color .15s;
}
.ntf-mark-all-btn:hover { background: #007836; color: #fff; }

/* ── List ──────────────────────────────────────────────────────────────────── */
.ntf-list { display: flex; flex-direction: column; gap: 10px; }

.ntf-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 14px 16px;
    position: relative;
    transition: border-color .15s, box-shadow .15s;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
}
.ntf-item:hover { border-color: #007836; box-shadow: 0 2px 10px rgba(0,120,54,.08); }
.ntf-item.is-unread { border-left: 3px solid #007836; background: #f0faf4; }

.ntf-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #007836;
    flex-shrink: 0;
    margin-top: 6px;
}
.ntf-item:not(.is-unread) .ntf-dot { background: #d1d5db; }

.ntf-icon-wrap {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.1rem;
}
.ntf-icon-food    { background: #fff7ed; color: #ea580c; }
.ntf-icon-colis   { background: #eff6ff; color: #2563eb; }
.ntf-icon-transport { background: #faf5ff; color: #7c3aed; }
.ntf-icon-general { background: #f1f5f9; color: #64748b; }

.ntf-body { flex: 1; min-width: 0; }
.ntf-item-title {
    font-size: .92rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ntf-item:not(.is-unread) .ntf-item-title { font-weight: 500; color: #374151; }
.ntf-item-body {
    font-size: .82rem;
    color: #6b7280;
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.ntf-item-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 6px;
    flex-wrap: wrap;
}
.ntf-time { font-size: .74rem; color: #9ca3af; }
.ntf-module-badge {
    font-size: .68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .03em;
    padding: 1px 7px;
    border-radius: 8px;
}
.ntf-module-food      { background: #fff7ed; color: #c2410c; }
.ntf-module-colis     { background: #eff6ff; color: #1d4ed8; }
.ntf-module-transport { background: #faf5ff; color: #6d28d9; }
.ntf-module-general   { background: #f1f5f9; color: #475569; }

.ntf-link-arrow {
    color: #007836;
    font-size: .85rem;
    flex-shrink: 0;
    align-self: center;
    opacity: 0;
    transition: opacity .15s;
}
.ntf-item:hover .ntf-link-arrow { opacity: 1; }

/* ── Empty state ───────────────────────────────────────────────────────────── */
.ntf-empty {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}
.ntf-empty-icon {
    font-size: 2.8rem;
    margin-bottom: 14px;
    color: #d1d5db;
}
.ntf-empty h3 { font-size: 1rem; color: #374151; margin-bottom: 6px; }
.ntf-empty p  { font-size: .85rem; }

/* ── Pagination ─────────────────────────────────────────────────────────────── */
.ntf-pagination { margin-top: 28px; display: flex; justify-content: center; }
.ntf-pagination .pagination { gap: 4px; }

/* ── Toast (feedback mark-as-read) ─────────────────────────────────────────── */
.ntf-toast {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%) translateY(60px);
    background: #111827;
    color: #fff;
    font-size: .82rem;
    padding: 8px 18px;
    border-radius: 20px;
    z-index: 9000;
    transition: transform .3s ease;
    pointer-events: none;
    white-space: nowrap;
}
.ntf-toast.show { transform: translateX(-50%) translateY(0); }
</style>
@endsection

@section('content')
<div class="ntf-page">

    {{-- Header --}}
    <div class="ntf-header">
        <h1 class="ntf-title">
            <i class="fas fa-bell" style="color:#007836;margin-right:8px;font-size:1.1rem;"></i>
            Mes notifications
        </h1>
        @if($unreadCount > 0)
            <span class="ntf-unread-chip">{{ $unreadCount }} non {{ $unreadCount > 1 ? 'lues' : 'lue' }}</span>
            <button class="ntf-mark-all-btn" id="ntfMarkAllBtn" onclick="ntfMarkAll()">
                <i class="fas fa-check-double"></i> Tout marquer lu
            </button>
        @endif
    </div>

    {{-- List --}}
    @if($notifications->isEmpty())
        <div class="ntf-empty">
            <div class="ntf-empty-icon"><i class="far fa-bell-slash"></i></div>
            <h3>Aucune notification</h3>
            <p>Vos notifications de commandes et mises à jour apparaîtront ici.</p>
        </div>
    @else
        <div class="ntf-list" id="ntfList">
            @foreach($notifications as $notif)
                @php
                    $module    = $notif->module();
                    $routePath = $notif->routePath();
                    $orderNo   = $notif->orderNo();
                    $isUnread  = $notif->isUnread();

                    $iconClass = match($module) {
                        'food'      => 'ntf-icon-food',
                        'colis'     => 'ntf-icon-colis',
                        'transport' => 'ntf-icon-transport',
                        default     => 'ntf-icon-general',
                    };
                    $iconName = match($module) {
                        'food'      => 'fa-utensils',
                        'colis'     => 'fa-box',
                        'transport' => 'fa-car',
                        default     => 'fa-bell',
                    };
                    $modBadgeClass = match($module) {
                        'food'      => 'ntf-module-food',
                        'colis'     => 'ntf-module-colis',
                        'transport' => 'ntf-module-transport',
                        default     => 'ntf-module-general',
                    };
                    $modLabel = match($module) {
                        'food'      => 'Repas',
                        'colis'     => 'Colis',
                        'transport' => 'Transport',
                        default     => 'Général',
                    };
                @endphp

                <div class="ntf-item {{ $isUnread ? 'is-unread' : '' }}"
                     id="ntf-{{ $notif->id }}"
                     data-id="{{ $notif->id }}"
                     data-href="{{ $routePath ? url($routePath) : '' }}"
                     onclick="ntfItemClick(this)">

                    <div class="ntf-dot"></div>

                    <div class="ntf-icon-wrap {{ $iconClass }}">
                        <i class="fas {{ $iconName }}"></i>
                    </div>

                    <div class="ntf-body">
                        <div class="ntf-item-title">{{ $notif->title ?? 'Notification' }}</div>
                        @if($notif->body)
                            <div class="ntf-item-body">{{ $notif->body }}</div>
                        @endif
                        <div class="ntf-item-meta">
                            <span class="ntf-time">
                                <i class="far fa-clock" style="margin-right:3px;"></i>
                                {{ $notif->created_at->diffForHumans() }}
                            </span>
                            <span class="ntf-module-badge {{ $modBadgeClass }}">{{ $modLabel }}</span>
                            @if($orderNo)
                                <span class="ntf-time">#{{ $orderNo }}</span>
                            @endif
                        </div>
                    </div>

                    @if($routePath)
                        <i class="fas fa-chevron-right ntf-link-arrow"></i>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($notifications->hasPages())
            <div class="ntf-pagination">
                {{ $notifications->links() }}
            </div>
        @endif
    @endif

</div>

{{-- Toast --}}
<div class="ntf-toast" id="ntfToast"></div>
@endsection

@section('script')
<script>
(function() {
    var CSRF = '{{ csrf_token() }}';

    function showToast(msg) {
        var t = document.getElementById('ntfToast');
        t.textContent = msg;
        t.classList.add('show');
        setTimeout(function() { t.classList.remove('show'); }, 2500);
    }

    function markRead(id) {
        fetch('/notifications/' + id + '/read', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            credentials: 'same-origin'
        });
        var el = document.getElementById('ntf-' + id);
        if (el) el.classList.remove('is-unread');
    }

    window.ntfItemClick = function(el) {
        var id   = el.dataset.id;
        var href = el.dataset.href;
        if (el.classList.contains('is-unread')) markRead(id);
        if (href) window.location.href = href;
    };

    window.ntfMarkAll = function() {
        var btn = document.getElementById('ntfMarkAllBtn');
        if (btn) { btn.disabled = true; btn.textContent = 'Traitement…'; }

        fetch('/notifications/read-all', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            credentials: 'same-origin'
        }).then(function() {
            document.querySelectorAll('.ntf-item.is-unread').forEach(function(el) {
                el.classList.remove('is-unread');
            });
            var chip = document.querySelector('.ntf-unread-chip');
            if (chip) chip.remove();
            if (btn) btn.remove();
            showToast('Toutes les notifications marquées comme lues');
            // reset nav bell badge
            var bell = document.getElementById('notifBadge');
            if (bell) bell.style.display = 'none';
        }).catch(function() {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check-double"></i> Tout marquer lu'; }
        });
    };
})();
</script>
@endsection
