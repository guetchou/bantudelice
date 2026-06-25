@extends('frontend.layouts.app-modern')

@section('title', 'Mes notifications')

@section('style')
<style>
/* ── Notifications inbox ──────────────────────────────────────────────────── */
.ntf-page {
    width: min(100%, 920px);
    margin: 0 auto;
    padding: clamp(18px, 4vw, 36px) 16px 88px;
}

.ntf-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 22px;
}
.ntf-header-copy { min-width: 0; }
.ntf-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: clamp(1.35rem, 3vw, 1.9rem);
    font-weight: 800;
    color: var(--bd-text, #111827);
    margin: 0;
    line-height: 1.15;
    overflow-wrap: anywhere;
}
.ntf-title i { color: #007836; font-size: 1.05em; }
.ntf-subtitle {
    margin: 8px 0 0;
    color: #64748b;
    font-size: .92rem;
    line-height: 1.5;
}
.ntf-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
    flex-shrink: 0;
}
.ntf-unread-chip {
    background: #007836;
    color: #fff;
    font-size: .75rem;
    font-weight: 800;
    padding: 5px 10px;
    border-radius: 999px;
    line-height: 1.2;
    white-space: nowrap;
}
.ntf-mark-all-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: .82rem;
    font-weight: 700;
    color: #007836;
    background: #fff;
    border: 1px solid rgba(0, 120, 54, .35);
    border-radius: 999px;
    padding: 8px 14px;
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s, color .15s, border-color .15s, opacity .15s;
}
.ntf-mark-all-btn:hover { background: #007836; color: #fff; border-color: #007836; }
.ntf-mark-all-btn:disabled { opacity: .65; cursor: wait; }

/* ── List ──────────────────────────────────────────────────────────────────── */
.ntf-list { display: flex; flex-direction: column; gap: 12px; }

.ntf-item {
    display: grid;
    grid-template-columns: 10px 44px minmax(0, 1fr) auto;
    align-items: flex-start;
    gap: 14px;
    width: 100%;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    padding: 16px;
    position: relative;
    transition: border-color .15s, box-shadow .15s, transform .15s, background .15s;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    text-align: left;
    font: inherit;
}
button.ntf-item { appearance: none; -webkit-appearance: none; }
.ntf-item:hover,
.ntf-item:focus-visible {
    border-color: #007836;
    box-shadow: 0 10px 28px rgba(0,120,54,.10);
    color: inherit;
    text-decoration: none;
    outline: none;
    transform: translateY(-1px);
}
.ntf-item.is-unread {
    border-color: rgba(0, 120, 54, .28);
    background: linear-gradient(135deg, #f0faf4 0%, #ffffff 72%);
}

.ntf-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: #007836;
    flex-shrink: 0;
    margin-top: 17px;
}
.ntf-item:not(.is-unread) .ntf-dot { background: #d1d5db; }

.ntf-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.05rem;
}
.ntf-icon-food      { background: #fff7ed; color: #ea580c; }
.ntf-icon-colis     { background: #eff6ff; color: #2563eb; }
.ntf-icon-transport { background: #faf5ff; color: #7c3aed; }
.ntf-icon-general   { background: #f1f5f9; color: #64748b; }

.ntf-body { min-width: 0; }
.ntf-item-title {
    font-size: .98rem;
    font-weight: 750;
    color: #111827;
    margin-bottom: 5px;
    line-height: 1.35;
    overflow-wrap: anywhere;
    word-break: normal;
}
.ntf-item:not(.is-unread) .ntf-item-title { font-weight: 650; color: #374151; }
.ntf-item-body {
    font-size: .86rem;
    color: #5f6b7a;
    line-height: 1.55;
    overflow-wrap: anywhere;
    white-space: normal;
}
.ntf-item-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
    flex-wrap: wrap;
}
.ntf-time { font-size: .76rem; color: #94a3b8; line-height: 1.4; }
.ntf-module-badge {
    font-size: .68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .04em;
    padding: 3px 8px;
    border-radius: 999px;
    line-height: 1.2;
}
.ntf-module-food      { background: #fff7ed; color: #c2410c; }
.ntf-module-colis     { background: #eff6ff; color: #1d4ed8; }
.ntf-module-transport { background: #faf5ff; color: #6d28d9; }
.ntf-module-general   { background: #f1f5f9; color: #475569; }

.ntf-link-arrow {
    color: #007836;
    font-size: .9rem;
    flex-shrink: 0;
    align-self: center;
    opacity: .55;
    transition: opacity .15s, transform .15s;
}
.ntf-item:hover .ntf-link-arrow,
.ntf-item:focus-visible .ntf-link-arrow { opacity: 1; transform: translateX(2px); }

/* ── Empty state ───────────────────────────────────────────────────────────── */
.ntf-empty {
    text-align: center;
    padding: 64px 20px;
    color: #94a3b8;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 22px;
}
.ntf-empty-icon {
    font-size: 2.8rem;
    margin-bottom: 14px;
    color: #d1d5db;
}
.ntf-empty h3 { font-size: 1.05rem; color: #374151; margin-bottom: 6px; }
.ntf-empty p  { font-size: .9rem; margin: 0; }

/* ── Pagination ─────────────────────────────────────────────────────────────── */
.ntf-pagination { margin-top: 28px; display: flex; justify-content: center; }
.ntf-pagination .pagination { gap: 4px; flex-wrap: wrap; }

/* ── Toast ─────────────────────────────────────────────────────────────────── */
.ntf-toast {
    position: fixed;
    bottom: max(24px, env(safe-area-inset-bottom));
    left: 50%;
    transform: translateX(-50%) translateY(60px);
    max-width: min(92vw, 520px);
    background: #111827;
    color: #fff;
    font-size: .86rem;
    padding: 10px 18px;
    border-radius: 999px;
    z-index: 9000;
    transition: transform .3s ease;
    pointer-events: none;
    text-align: center;
    line-height: 1.4;
}
.ntf-toast.show { transform: translateX(-50%) translateY(0); }

@media (max-width: 640px) {
    .ntf-header { flex-direction: column; align-items: stretch; }
    .ntf-header-actions { justify-content: flex-start; }
    .ntf-item {
        grid-template-columns: 8px 38px minmax(0, 1fr);
        gap: 11px;
        padding: 14px;
        border-radius: 16px;
    }
    .ntf-icon-wrap { width: 38px; height: 38px; border-radius: 12px; }
    .ntf-dot { margin-top: 14px; }
    .ntf-link-arrow { display: none; }
}
</style>
@endsection

@section('content')
<div class="ntf-page">

    {{-- Header --}}
    <div class="ntf-header">
        <div class="ntf-header-copy">
            <h1 class="ntf-title">
                <i class="fas fa-bell" aria-hidden="true"></i>
                <span>Mes notifications</span>
            </h1>
            <p class="ntf-subtitle">Retrouvez ici les alertes liées à vos commandes, livraisons, colis et trajets.</p>
        </div>

        @if($unreadCount > 0)
            <div class="ntf-header-actions">
                <span class="ntf-unread-chip">{{ $unreadCount }} non {{ $unreadCount > 1 ? 'lues' : 'lue' }}</span>
                <button type="button" class="ntf-mark-all-btn" id="ntfMarkAllBtn" onclick="ntfMarkAll()">
                    <i class="fas fa-check-double" aria-hidden="true"></i> Tout marquer lu
                </button>
            </div>
        @endif
    </div>

    {{-- List --}}
    @if($notifications->isEmpty())
        <div class="ntf-empty">
            <div class="ntf-empty-icon"><i class="far fa-bell-slash" aria-hidden="true"></i></div>
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
                    $targetUrl = null;

                    if ($routePath) {
                        $targetUrl = \Illuminate\Support\Str::startsWith($routePath, ['http://', 'https://'])
                            ? $routePath
                            : url($routePath);
                    }

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

                @if($targetUrl)
                    <a class="ntf-item {{ $isUnread ? 'is-unread' : '' }}"
                       id="ntf-{{ $notif->id }}"
                       href="{{ $targetUrl }}"
                       data-id="{{ $notif->id }}"
                       data-read-url="{{ route('user.notifications.read', $notif->id) }}">
                @else
                    <button type="button"
                            class="ntf-item {{ $isUnread ? 'is-unread' : '' }}"
                            id="ntf-{{ $notif->id }}"
                            data-id="{{ $notif->id }}"
                            data-read-url="{{ route('user.notifications.read', $notif->id) }}"
                            onclick="ntfMarkSingle(this)">
                @endif

                    <span class="ntf-dot" aria-hidden="true"></span>

                    <span class="ntf-icon-wrap {{ $iconClass }}" aria-hidden="true">
                        <i class="fas {{ $iconName }}"></i>
                    </span>

                    <span class="ntf-body">
                        <span class="ntf-item-title">{{ $notif->title ?? 'Notification' }}</span>
                        @if($notif->body)
                            <span class="ntf-item-body">{{ $notif->body }}</span>
                        @endif
                        <span class="ntf-item-meta">
                            <span class="ntf-time">
                                <i class="far fa-clock" aria-hidden="true" style="margin-right:3px;"></i>
                                {{ $notif->created_at->diffForHumans() }}
                            </span>
                            <span class="ntf-module-badge {{ $modBadgeClass }}">{{ $modLabel }}</span>
                            @if($orderNo)
                                <span class="ntf-time">#{{ $orderNo }}</span>
                            @endif
                        </span>
                    </span>

                    @if($targetUrl)
                        <i class="fas fa-chevron-right ntf-link-arrow" aria-hidden="true"></i>
                    @endif

                @if($targetUrl)
                    </a>
                @else
                    </button>
                @endif
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
<div class="ntf-toast" id="ntfToast" role="status" aria-live="polite"></div>
@endsection

@section('script')
<script>
(function() {
    var CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    var READ_ALL_URL = @json(route('user.notifications.read_all'));

    function showToast(msg) {
        var t = document.getElementById('ntfToast');
        if (!t) return;
        t.textContent = msg;
        t.classList.add('show');
        setTimeout(function() { t.classList.remove('show'); }, 2500);
    }

    function updateHeaderBadge(count) {
        var bell = document.getElementById('notifBadge');
        if (!bell) return;

        if (count > 0) {
            bell.textContent = String(count);
            bell.style.display = '';
        } else {
            bell.textContent = '0';
            bell.style.display = 'none';
        }
    }

    function markRead(el) {
        if (!el || !el.classList.contains('is-unread')) {
            return Promise.resolve();
        }

        var url = el.dataset.readUrl;
        if (!url) {
            el.classList.remove('is-unread');
            return Promise.resolve();
        }

        return fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        }).then(function(response) {
            if (!response.ok) throw new Error('Lecture non confirmée');
            el.classList.remove('is-unread');
        });
    }

    document.querySelectorAll('a.ntf-item').forEach(function(link) {
        link.addEventListener('click', function(event) {
            if (!link.classList.contains('is-unread')) return;

            event.preventDefault();
            var href = link.href;
            markRead(link).catch(function() {
                // Ne bloque pas la navigation si le marquage échoue ponctuellement.
            }).finally(function() {
                window.location.href = href;
            });
        });
    });

    window.ntfMarkSingle = function(el) {
        markRead(el).then(function() {
            showToast('Notification marquée comme lue');
        }).catch(function() {
            showToast('Impossible de marquer cette notification comme lue');
        });
    };

    window.ntfMarkAll = function() {
        var btn = document.getElementById('ntfMarkAllBtn');
        var originalHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i> Traitement…';
        }

        fetch(READ_ALL_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        }).then(function(response) {
            if (!response.ok) throw new Error('Lecture globale non confirmée');
            return response.json().catch(function() { return {}; });
        }).then(function() {
            document.querySelectorAll('.ntf-item.is-unread').forEach(function(el) {
                el.classList.remove('is-unread');
            });
            var chip = document.querySelector('.ntf-unread-chip');
            if (chip) chip.remove();
            if (btn) btn.remove();
            updateHeaderBadge(0);
            showToast('Toutes les notifications sont marquées comme lues');
        }).catch(function() {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml || '<i class="fas fa-check-double" aria-hidden="true"></i> Tout marquer lu';
            }
            showToast('Impossible de traiter les notifications pour le moment');
        });
    };
})();
</script>
@endsection
