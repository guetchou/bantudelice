@php
    $u = auth()->user();
    $uName = $u->name ?? 'Utilisateur';
    $uEmail = $u->email ?? '';
    $uType = $u->type ?? 'user';
    $uInitial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($uName, 0, 1)) : strtoupper(substr($uName, 0, 1));
    $uAvatar = $u && method_exists($u, 'avatarUrl') ? $u->avatarUrl() : null;

    $roleLabel = match ($uType) {
        'admin' => 'Administrateur',
        'restaurant' => 'Restaurant',
        'driver', 'delivery' => 'Livreur',
        default => 'Client',
    };

    $profileLinks = match ($uType) {
        'admin' => [
            ['href' => route('admin.profile'), 'icon' => 'fas fa-user-circle', 'label' => 'Mon profil'],
            ['href' => route('charge.index'), 'icon' => 'fas fa-cog', 'label' => 'Paramètres plateforme'],
        ],
        'restaurant' => [
            ['href' => route('restaurant.profile'), 'icon' => 'fas fa-store', 'label' => 'Profil restaurant'],
            ['href' => route('r_earnings.index'), 'icon' => 'fas fa-wallet', 'label' => 'Mes revenus'],
        ],
        'driver', 'delivery' => [
            ['href' => route('driver.deliveries'), 'icon' => 'fas fa-motorcycle', 'label' => 'Mes livraisons'],
            ['href' => route('driver_payout'), 'icon' => 'fas fa-money-bill-wave', 'label' => 'Mes gains'],
        ],
        default => [
            ['href' => route('user.dashboard'), 'icon' => 'fas fa-gauge-high', 'label' => 'Mon dashboard'],
            ['href' => route('user.dashboard.profile'), 'icon' => 'fas fa-user', 'label' => 'Mon profil'],
            ['href' => route('user.orders'), 'icon' => 'fas fa-receipt', 'label' => 'Mes commandes'],
        ],
    };

    $passwordRoute = match ($uType) {
        'admin' => route('admin.profile_update'),
        'restaurant' => route('restaurant.profile.profile_update'),
        default => route('profile.password'),
    };

    $restaurantNotificationPollUrl = app('router')->has('restaurant.notifications.poll')
        ? route('restaurant.notifications.poll')
        : url('/restaurant/notifications');
@endphp

<div id="bdDrawerOverlay" class="bd-drawer-overlay" onclick="bdDrawerClose()" aria-hidden="true"></div>

<aside id="bdProfileDrawer" class="bd-drawer" role="dialog" aria-label="Profil utilisateur" aria-hidden="true">
    <div class="bd-drawer__header">
        <div class="bd-drawer__avatar">
            @if($uAvatar)
                <img src="{{ $uAvatar }}" alt="{{ $uName }}">
            @else
                {{ $uInitial }}
            @endif
        </div>
        <div class="bd-drawer__identity">
            <span class="bd-drawer__name">{{ $uName }}</span>
            <span class="bd-drawer__role">{{ $roleLabel }}</span>
            @if($uEmail)
                <span class="bd-drawer__email">{{ $uEmail }}</span>
            @endif
        </div>
        <button type="button" class="bd-drawer__close" onclick="bdDrawerClose()" aria-label="Fermer">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <nav class="bd-drawer__nav">
        @foreach($profileLinks as $link)
            <a href="{{ $link['href'] }}" class="bd-drawer__link">
                <i class="{{ $link['icon'] }}"></i> {{ $link['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="bd-drawer__divider"></div>

    <div class="bd-drawer__section">
        <button type="button" class="bd-drawer__toggle" onclick="bdTogglePassword()" aria-expanded="false" id="bdPasswordToggle">
            <i class="fas fa-lock"></i> Changer le mot de passe
            <i class="fas fa-chevron-down bd-drawer__chevron" id="bdPasswordChevron"></i>
        </button>

        <div class="bd-drawer__password-form" id="bdPasswordForm" style="display:none;">
            <form method="POST" action="{{ $passwordRoute }}" id="bdPasswordChangeForm">
                @csrf
                <div class="bd-drawer__field">
                    <label>Mot de passe actuel</label>
                    <input type="password" name="current_password" required autocomplete="current-password">
                </div>
                <div class="bd-drawer__field">
                    <label>Nouveau mot de passe</label>
                    <input type="password" name="password" required autocomplete="new-password" minlength="8">
                </div>
                <div class="bd-drawer__field">
                    <label>Confirmer</label>
                    <input type="password" name="password_confirmation" required autocomplete="new-password">
                </div>
                <button type="submit" class="bd-drawer__submit">Enregistrer</button>
            </form>
            @if(session('password_success'))
                <p class="bd-drawer__success"><i class="fas fa-check-circle"></i> {{ session('password_success') }}</p>
            @endif
            @if($errors->has('current_password') || $errors->has('password'))
                <p class="bd-drawer__error"><i class="fas fa-exclamation-circle"></i> {{ $errors->first('current_password') ?: $errors->first('password') }}</p>
            @endif
        </div>
    </div>

    <div class="bd-drawer__divider"></div>

    <div class="bd-drawer__section">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="bd-drawer__logout">
                <i class="fas fa-sign-out-alt"></i> Se déconnecter
            </button>
        </form>
    </div>
</aside>

<style>
.bd-drawer-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1050;
    background: rgba(15, 23, 42, .42);
    backdrop-filter: blur(2px);
}
.bd-drawer-overlay.is-open { display: block; }
.bd-drawer {
    position: fixed;
    top: 0;
    right: 0;
    z-index: 1051;
    width: min(100vw, 380px);
    height: 100vh;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    background: #fff;
    box-shadow: -16px 0 44px rgba(15, 23, 42, .18);
    transform: translateX(100%);
    transition: transform .24s ease;
}
.bd-drawer.is-open { transform: translateX(0); }
.bd-drawer__header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 16px 16px;
    color: #fff;
    background: linear-gradient(135deg, #009543 0%, #007836 100%);
}
.bd-drawer__avatar {
    width: 46px;
    height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    overflow: hidden;
    border: 2px solid rgba(255,255,255,.52);
    border-radius: 50%;
    background: rgba(255,255,255,.22);
    font-size: 1rem;
    font-weight: 800;
}
.bd-drawer__avatar img { width: 100%; height: 100%; object-fit: cover; }
.bd-drawer__identity { min-width: 0; flex: 1; }
.bd-drawer__name,
.bd-drawer__role,
.bd-drawer__email { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.bd-drawer__name { font-size: .95rem; font-weight: 800; }
.bd-drawer__role { margin-top: 1px; font-size: .76rem; opacity: .84; }
.bd-drawer__email { margin-top: 1px; font-size: .72rem; opacity: .68; }
.bd-drawer__close {
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    border-radius: 999px;
    background: rgba(255,255,255,.16);
    color: #fff;
    cursor: pointer;
}
.bd-drawer__close:hover { background: rgba(255,255,255,.28); }
.bd-drawer__nav { padding: 8px 0; }
.bd-drawer__link,
.bd-drawer__toggle,
.bd-drawer__logout {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 20px;
    border: 0;
    background: none;
    color: #374151;
    text-align: left;
    text-decoration: none;
    font-size: .88rem;
    font-weight: 600;
    cursor: pointer;
}
.bd-drawer__link i,
.bd-drawer__toggle i:first-child,
.bd-drawer__logout i { width: 16px; text-align: center; color: #6b7280; }
.bd-drawer__link:hover,
.bd-drawer__toggle:hover { background: #f0fdf4; color: #009543; text-decoration: none; }
.bd-drawer__link:hover i,
.bd-drawer__toggle:hover i:first-child { color: #009543; }
.bd-drawer__logout { color: #dc2626; }
.bd-drawer__logout:hover { background: #fef2f2; }
.bd-drawer__divider { height: 1px; flex: 0 0 auto; margin: 4px 0; background: #f3f4f6; }
.bd-drawer__section { padding: 4px 0; }
.bd-drawer__chevron { margin-left: auto; font-size: .7rem; color: #9ca3af; transition: transform .2s; }
.bd-drawer__chevron.is-open { transform: rotate(180deg); }
.bd-drawer__password-form { padding: 12px 20px 16px; background: #f9fafb; }
.bd-drawer__field { margin-bottom: 10px; }
.bd-drawer__field label { display: block; margin-bottom: 4px; color: #6b7280; font-size: .78rem; font-weight: 700; }
.bd-drawer__field input {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: .85rem;
}
.bd-drawer__field input:focus { outline: none; border-color: #009543; }
.bd-drawer__submit {
    width: 100%;
    margin-top: 4px;
    padding: 9px;
    border: 0;
    border-radius: 6px;
    background: #009543;
    color: #fff;
    font-size: .85rem;
    font-weight: 700;
    cursor: pointer;
}
.bd-drawer__success { color: #16a34a; font-size: .8rem; margin-top: 8px; display: flex; align-items: center; gap: 6px; }
.bd-drawer__error { color: #dc2626; font-size: .8rem; margin-top: 8px; display: flex; align-items: center; gap: 6px; }
</style>

<script>
function bdDrawerOpen() {
    var drawer = document.getElementById('bdProfileDrawer');
    var overlay = document.getElementById('bdDrawerOverlay');
    if (!drawer || !overlay) return;
    drawer.classList.add('is-open');
    overlay.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
}
function bdDrawerClose() {
    var drawer = document.getElementById('bdProfileDrawer');
    var overlay = document.getElementById('bdDrawerOverlay');
    if (!drawer || !overlay) return;
    drawer.classList.remove('is-open');
    overlay.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
}
function bdTogglePassword() {
    var form = document.getElementById('bdPasswordForm');
    var chevron = document.getElementById('bdPasswordChevron');
    var btn = document.getElementById('bdPasswordToggle');
    if (!form || !chevron || !btn) return;
    var open = form.style.display === 'none' || form.style.display === '';
    form.style.display = open ? 'block' : 'none';
    chevron.classList.toggle('is-open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') bdDrawerClose();
});
</script>

@if($uType === 'restaurant')
<style>
#myModal2.modal.right .modal-dialog {
    position: fixed !important;
    top: var(--bd-topbar-h, 60px);
    right: 0;
    bottom: 0;
    width: min(100vw, 440px) !important;
    max-width: 100vw;
    height: calc(100vh - var(--bd-topbar-h, 60px));
    margin: 0;
}
#myModal2 .modal-content {
    height: 100%;
    display: flex;
    flex-direction: column;
    border: 0;
    border-radius: 16px 0 0 16px;
    overflow: hidden;
    background: var(--bd-surface, #fff);
    color: var(--bd-text, #111827);
    box-shadow: -18px 0 45px rgba(15, 23, 42, .18);
}
#myModal2 .modal-header {
    flex: 0 0 auto;
    align-items: center;
    justify-content: space-between;
    padding: 18px !important;
    border-bottom: 1px solid var(--bd-border, #e5e7eb);
    background: var(--bd-surface, #fff);
}
#myModal2 .modal-title {
    margin: 0;
    color: var(--bd-text, #111827);
    font-size: 17px;
    font-weight: 800;
    line-height: 1.25;
}
#myModal2 .close {
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0;
    padding: 0;
    border-radius: 999px;
    color: var(--bd-text-2, #4b5563);
    opacity: 1;
}
#myModal2 #notiBody {
    flex: 1 1 auto;
    min-height: 0;
    margin: 0 !important;
    padding: 12px !important;
    overflow-y: auto;
    background: var(--bd-bg, #f4f5f7);
}
#myModal2 .dropdown-divider { display: none; }
#myModal2 .bd-restaurant-notification,
#myModal2 .dropdown-item {
    width: 100%;
    display: grid;
    grid-template-columns: 38px minmax(0, 1fr) auto;
    gap: 12px;
    align-items: center;
    margin-bottom: 10px;
    padding: 12px;
    border: 1px solid var(--bd-border, #e5e7eb);
    border-radius: 14px;
    background: var(--bd-surface, #fff);
    color: var(--bd-text, #111827);
    text-decoration: none;
    white-space: normal;
    box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
}
#myModal2 .bd-restaurant-notification:hover,
#myModal2 .dropdown-item:hover {
    border-color: var(--bd-green, #009543);
    color: var(--bd-text, #111827);
    text-decoration: none;
}
#myModal2 .bd-restaurant-notification__icon {
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: rgba(0, 149, 67, .12);
    color: var(--bd-green, #009543);
}
#myModal2 .bd-restaurant-notification__copy { min-width: 0; }
#myModal2 .bd-restaurant-notification__title {
    display: block;
    color: var(--bd-text, #111827);
    font-size: 13px;
    font-weight: 800;
    line-height: 1.35;
    overflow-wrap: anywhere;
}
#myModal2 .bd-restaurant-notification__text {
    display: block;
    margin-top: 2px;
    color: var(--bd-text-2, #4b5563);
    font-size: 12px;
    line-height: 1.45;
    overflow-wrap: anywhere;
}
#myModal2 .bd-restaurant-notification__time {
    align-self: start;
    padding-top: 2px;
    color: var(--bd-text-3, #9ca3af);
    font-size: 11px;
    white-space: nowrap;
}
#myModal2 .bd-restaurant-notification--empty {
    min-height: 220px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 28px 18px;
    border: 1px dashed var(--bd-border, #e5e7eb);
    border-radius: 18px;
    background: var(--bd-surface, #fff);
    color: var(--bd-text-2, #4b5563);
    text-align: center;
}
#myModal2 .bd-restaurant-notification--empty i {
    width: 48px;
    height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    border-radius: 16px;
    background: rgba(0, 149, 67, .10);
    color: var(--bd-green, #009543);
    font-size: 20px;
}
#myModal2 .bd-restaurant-notification--empty strong {
    color: var(--bd-text, #111827);
    font-size: 14px;
}
#myModal2 .bd-restaurant-notification--empty span {
    margin-top: 4px;
    font-size: 12px;
}
#notiBell.bd-restaurant-notif-badge--hidden { display: none !important; }
[data-theme="dark"] #myModal2 .bd-restaurant-notification,
[data-theme="dark"] #myModal2 .dropdown-item,
[data-theme="dark"] #myModal2 .bd-restaurant-notification--empty {
    background: var(--bd-surface, #1a1d27);
    border-color: var(--bd-border, rgba(255,255,255,.09));
}
@media (max-width: 520px) {
    #myModal2.modal.right .modal-dialog { top: 0; width: 100vw !important; height: 100vh; }
    #myModal2 .modal-content { border-radius: 0; }
    #myModal2 .bd-restaurant-notification { grid-template-columns: 36px minmax(0, 1fr); }
    #myModal2 .bd-restaurant-notification__time { grid-column: 2; white-space: normal; }
}
</style>
<script>
(function () {
    var RESTAURANT_NOTIFICATION_POLL_URL = @json($restaurantNotificationPollUrl);
    var initialized = false;

    function safeText(value) {
        return (value || '').toString().replace(/\s+/g, ' ').trim();
    }

    function notificationCount() {
        var badge = document.getElementById('notiBell');
        return parseInt(safeText(badge ? badge.textContent : ''), 10) || 0;
    }

    function setTitle() {
        var title = document.getElementById('notiTitle');
        if (!title) return;
        var count = notificationCount();
        title.textContent = count > 0 ? count + ' notification' + (count > 1 ? 's' : '') : 'Notifications';
    }

    function updateBadge() {
        var badge = document.getElementById('notiBell');
        if (!badge) return;
        var count = notificationCount();
        badge.classList.toggle('bd-restaurant-notif-badge--hidden', count <= 0);
        if (count <= 0) badge.textContent = '';
    }

    function setRestaurantNotificationPollUrl() {
        try {
            if (typeof notificationConfig !== 'undefined' && notificationConfig) {
                notificationConfig.pollUrl = RESTAURANT_NOTIFICATION_POLL_URL;
            }
        } catch (error) {}
    }

    function renderEmptyState(body) {
        if (!body || body.querySelector('.bd-restaurant-notification--empty')) return;
        body.innerHTML = '';
        var empty = document.createElement('div');
        empty.className = 'bd-restaurant-notification--empty';
        empty.innerHTML = '<i class="far fa-bell-slash" aria-hidden="true"></i><strong>Aucune alerte pour le moment</strong><span>Les nouvelles commandes apparaîtront ici automatiquement.</span>';
        body.appendChild(empty);
    }

    function ensureFallback() {
        var body = document.getElementById('notiBody');
        if (!body) return;
        setRestaurantNotificationPollUrl();
        updateBadge();
        setTitle();
        if (!body.children.length && !safeText(body.textContent)) {
            renderEmptyState(body);
        }
    }

    function enhanceNotificationList() {
        var body = document.getElementById('notiBody');
        if (!body) return;

        setRestaurantNotificationPollUrl();
        updateBadge();
        setTitle();

        if (body.querySelector('.bd-restaurant-notification--empty')) return;

        var items = Array.prototype.slice.call(body.querySelectorAll('a.dropdown-item, a.bd-restaurant-notification'));
        var hasEmptyAnchor = items.some(function (item) {
            return safeText(item.textContent).toLowerCase().indexOf('aucune') !== -1;
        });

        if (hasEmptyAnchor || !items.length) {
            renderEmptyState(body);
            return;
        }

        items.forEach(function (item) {
            if (item.classList.contains('bd-restaurant-notification')) return;

            var raw = safeText(item.textContent);
            var orderMatch = raw.match(/#?([A-Za-z0-9-]{3,})/);
            var orderNo = orderMatch ? orderMatch[1] : '';
            var timeNode = item.querySelector('.text-muted');
            var time = safeText(timeNode ? timeNode.textContent : '').replace(orderNo, '').trim();

            item.className = 'bd-restaurant-notification';
            item.innerHTML = '';

            var icon = document.createElement('span');
            icon.className = 'bd-restaurant-notification__icon';
            icon.innerHTML = '<i class="fas fa-receipt" aria-hidden="true"></i>';

            var copy = document.createElement('span');
            copy.className = 'bd-restaurant-notification__copy';

            var title = document.createElement('span');
            title.className = 'bd-restaurant-notification__title';
            title.textContent = orderNo ? 'Commande #' + orderNo : 'Nouvelle commande';

            var text = document.createElement('span');
            text.className = 'bd-restaurant-notification__text';
            text.textContent = 'Action requise : ouvrir la commande pour accepter, préparer ou vérifier son statut.';

            var meta = document.createElement('span');
            meta.className = 'bd-restaurant-notification__time';
            meta.textContent = time || 'À l’instant';

            copy.appendChild(title);
            copy.appendChild(text);
            item.appendChild(icon);
            item.appendChild(copy);
            item.appendChild(meta);
        });
    }

    function refreshNotifications() {
        try {
            if (typeof get_notification === 'function') {
                get_notification();
            }
        } catch (error) {}
        window.setTimeout(enhanceNotificationList, 350);
        window.setTimeout(ensureFallback, 1200);
        window.setTimeout(ensureFallback, 3000);
    }

    function initRestaurantNotifications() {
        if (initialized) return;
        initialized = true;

        setRestaurantNotificationPollUrl();
        enhanceNotificationList();
        ensureFallback();

        var modal = document.getElementById('myModal2');
        if (modal && window.jQuery) {
            window.jQuery(modal).on('show.bs.modal shown.bs.modal', function () {
                ensureFallback();
                refreshNotifications();
            });
        }

        var body = document.getElementById('notiBody');
        if (body && window.MutationObserver) {
            var observer = new MutationObserver(function () {
                enhanceNotificationList();
            });
            observer.observe(body, { childList: true, subtree: true });
        }

        window.setTimeout(ensureFallback, 600);
        window.setTimeout(ensureFallback, 2000);
    }

    setRestaurantNotificationPollUrl();
    ensureFallback();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRestaurantNotifications);
    } else {
        initRestaurantNotifications();
    }

    window.addEventListener('load', function () {
        initRestaurantNotifications();
        ensureFallback();
    });
})();
</script>
@endif
