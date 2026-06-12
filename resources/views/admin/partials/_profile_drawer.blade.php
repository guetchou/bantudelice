@php
    $u        = auth()->user();
    $uName    = $u->name ?? 'Utilisateur';
    $uEmail   = $u->email ?? '';
    $uType    = $u->type ?? 'user';
    $uInitial = strtoupper(substr($uName, 0, 1));

    $roleLabel = match($uType) {
        'admin'      => 'Administrateur',
        'restaurant' => 'Restaurant',
        'driver','delivery' => 'Livreur',
        default      => 'Client',
    };
@endphp

{{-- ── Overlay ── --}}
<div id="bdDrawerOverlay" class="bd-drawer-overlay" onclick="bdDrawerClose()" aria-hidden="true"></div>

{{-- ── Drawer ── --}}
<aside id="bdProfileDrawer" class="bd-drawer" role="dialog" aria-label="Profil utilisateur" aria-hidden="true">

    {{-- Header --}}
    <div class="bd-drawer__header">
        <div class="bd-drawer__avatar">{{ $uInitial }}</div>
        <div class="bd-drawer__identity">
            <span class="bd-drawer__name">{{ $uName }}</span>
            <span class="bd-drawer__role">{{ $roleLabel }}</span>
            @if($uEmail)
                <span class="bd-drawer__email">{{ $uEmail }}</span>
            @endif
        </div>
        <button class="bd-drawer__close" onclick="bdDrawerClose()" aria-label="Fermer">
            <i class="fas fa-times"></i>
        </button>
    </div>

    {{-- Nav contextuelle selon rôle --}}
    <nav class="bd-drawer__nav">

        @if($uType === 'admin')
            <a href="{{ route('admin.profile') }}" class="bd-drawer__link">
                <i class="fas fa-user-circle"></i> Mon profil
            </a>
            <a href="{{ route('charge.index') }}" class="bd-drawer__link">
                <i class="fas fa-cog"></i> Paramètres plateforme
            </a>

        @elseif($uType === 'restaurant')
            <a href="{{ route('restaurant.profile') }}" class="bd-drawer__link">
                <i class="fas fa-store"></i> Profil restaurant
            </a>
            <a href="{{ route('restaurant_payout') }}" class="bd-drawer__link">
                <i class="fas fa-wallet"></i> Mes revenus
            </a>

        @elseif(in_array($uType, ['driver', 'delivery']))
            <a href="{{ route('driver.deliveries') }}" class="bd-drawer__link">
                <i class="fas fa-motorcycle"></i> Mes livraisons
            </a>
            <a href="{{ route('driver_payout') }}" class="bd-drawer__link">
                <i class="fas fa-money-bill-wave"></i> Mes gains
            </a>

        @else
            <a href="{{ route('user.profile') }}" class="bd-drawer__link">
                <i class="fas fa-user"></i> Mon profil
            </a>
            <a href="{{ route('user.orders') }}" class="bd-drawer__link">
                <i class="fas fa-receipt"></i> Mes commandes
            </a>
        @endif

    </nav>

    <div class="bd-drawer__divider"></div>

    {{-- Changement de mot de passe --}}
    <div class="bd-drawer__section">
        <button class="bd-drawer__toggle" onclick="bdTogglePassword()" aria-expanded="false" id="bdPasswordToggle">
            <i class="fas fa-lock"></i> Changer le mot de passe
            <i class="fas fa-chevron-down bd-drawer__chevron" id="bdPasswordChevron"></i>
        </button>

        <div class="bd-drawer__password-form" id="bdPasswordForm" style="display:none;">
            @php
                $passwordRoute = match($uType) {
                    'admin'      => route('admin.profile_update'),
                    'restaurant' => route('restaurant.profile.profile_update'),
                    default      => route('profile.password'),
                };
            @endphp
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

    {{-- Déconnexion --}}
    <div class="bd-drawer__section">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="bd-drawer__logout">
                <i class="fas fa-sign-out-alt"></i> Se déconnecter
            </button>
        </form>
    </div>

</aside>

{{-- ── Styles ── --}}
<style>
.bd-drawer-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 1050;
    backdrop-filter: blur(2px);
}
.bd-drawer-overlay.is-open { display: block; }

.bd-drawer {
    position: fixed;
    top: 0;
    right: 0;
    height: 100%;
    width: 360px;
    max-width: 100vw;
    background: #fff;
    z-index: 1051;
    transform: translateX(100%);
    transition: transform .28s cubic-bezier(.4,0,.2,1);
    display: flex;
    flex-direction: column;
    box-shadow: -4px 0 24px rgba(0,0,0,.12);
    overflow-y: auto;
}
.bd-drawer.is-open { transform: translateX(0); }

.bd-drawer__header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 16px 16px;
    background: linear-gradient(135deg, #009543 0%, #007836 100%);
    color: #fff;
    flex-shrink: 0;
}
.bd-drawer__avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: rgba(255,255,255,.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    font-weight: 800;
    flex-shrink: 0;
    border: 2px solid rgba(255,255,255,.5);
}
.bd-drawer__identity { flex: 1; min-width: 0; }
.bd-drawer__name  { display: block; font-weight: 700; font-size: .95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bd-drawer__role  { display: block; font-size: .75rem; opacity: .8; margin-top: 1px; }
.bd-drawer__email { display: block; font-size: .72rem; opacity: .65; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bd-drawer__close {
    background: rgba(255,255,255,.15);
    border: none;
    color: #fff;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background .15s;
}
.bd-drawer__close:hover { background: rgba(255,255,255,.3); }

.bd-drawer__nav { padding: 8px 0; flex-shrink: 0; }
.bd-drawer__link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 20px;
    color: #374151;
    text-decoration: none;
    font-size: .88rem;
    font-weight: 500;
    transition: background .15s, color .15s;
}
.bd-drawer__link i { width: 16px; text-align: center; color: #6b7280; flex-shrink: 0; }
.bd-drawer__link:hover { background: #f0fdf4; color: #009543; }
.bd-drawer__link:hover i { color: #009543; }

.bd-drawer__divider { height: 1px; background: #f3f4f6; margin: 4px 0; flex-shrink: 0; }

.bd-drawer__section { padding: 4px 0; flex-shrink: 0; }

.bd-drawer__toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 11px 20px;
    background: none;
    border: none;
    color: #374151;
    font-size: .88rem;
    font-weight: 500;
    cursor: pointer;
    text-align: left;
    transition: background .15s;
}
.bd-drawer__toggle i:first-child { width: 16px; text-align: center; color: #6b7280; flex-shrink: 0; }
.bd-drawer__toggle:hover { background: #f9fafb; }
.bd-drawer__chevron { margin-left: auto; font-size: .7rem; transition: transform .2s; color: #9ca3af; }
.bd-drawer__chevron.is-open { transform: rotate(180deg); }

.bd-drawer__password-form { padding: 12px 20px 16px; background: #f9fafb; }
.bd-drawer__field { margin-bottom: 10px; }
.bd-drawer__field label { display: block; font-size: .78rem; font-weight: 600; color: #6b7280; margin-bottom: 4px; }
.bd-drawer__field input {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: .85rem;
    outline: none;
    transition: border-color .15s;
}
.bd-drawer__field input:focus { border-color: #009543; }
.bd-drawer__submit {
    width: 100%;
    padding: 9px;
    background: #009543;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: 4px;
    transition: background .15s;
}
.bd-drawer__submit:hover { background: #007836; }
.bd-drawer__success { color: #16a34a; font-size: .8rem; margin-top: 8px; display: flex; align-items: center; gap: 6px; }
.bd-drawer__error   { color: #dc2626; font-size: .8rem; margin-top: 8px; display: flex; align-items: center; gap: 6px; }

.bd-drawer__logout {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 11px 20px;
    background: none;
    border: none;
    color: #dc2626;
    font-size: .88rem;
    font-weight: 600;
    cursor: pointer;
    text-align: left;
    transition: background .15s;
}
.bd-drawer__logout i { width: 16px; text-align: center; flex-shrink: 0; }
.bd-drawer__logout:hover { background: #fef2f2; }

@media (max-width: 480px) {
    .bd-drawer { width: 100vw; }
}
</style>

{{-- ── JS ── --}}
<script>
function bdDrawerOpen() {
    document.getElementById('bdProfileDrawer').classList.add('is-open');
    document.getElementById('bdDrawerOverlay').classList.add('is-open');
    document.getElementById('bdProfileDrawer').setAttribute('aria-hidden', 'false');
}
function bdDrawerClose() {
    document.getElementById('bdProfileDrawer').classList.remove('is-open');
    document.getElementById('bdDrawerOverlay').classList.remove('is-open');
    document.getElementById('bdProfileDrawer').setAttribute('aria-hidden', 'true');
}
function bdTogglePassword() {
    var form    = document.getElementById('bdPasswordForm');
    var chevron = document.getElementById('bdPasswordChevron');
    var btn     = document.getElementById('bdPasswordToggle');
    var open    = form.style.display === 'none';
    form.style.display = open ? 'block' : 'none';
    chevron.classList.toggle('is-open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') bdDrawerClose();
});
</script>
