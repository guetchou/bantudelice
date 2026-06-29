<div id="admProfileOverlay" class="adm-profile-overlay" onclick="admProfileClose()" aria-hidden="true"></div>

<aside id="admProfileDrawer" class="adm-profile-drawer" role="dialog" aria-label="Profil administrateur" aria-hidden="true">
    <header class="adm-profile-head">
        <div class="adm-profile-avatar">{{ strtoupper(substr($adminUser->name ?? 'A', 0, 1)) }}</div>
        <div class="adm-profile-identity">
            <strong>{{ $adminUser->name ?? 'Administrateur' }}</strong>
            <span>{{ $adminUser->email ?? '' }}</span>
            <small>{{ $adminUser->isSuperAdmin() ? 'Super administrateur' : 'Administrateur' }}</small>
        </div>
        <button type="button" class="adm-profile-close" onclick="admProfileClose()" aria-label="Fermer">
            <i class="fas fa-times"></i>
        </button>
    </header>

    <nav class="adm-profile-nav">
        <a href="{{ route('admin.profile') }}">
            <i class="fas fa-user-circle"></i>
            Mon profil
        </a>
        <a href="{{ route('admin.portal') }}">
            <i class="fas fa-border-all"></i>
            Portail des applications
        </a>
        <a href="{{ route('admin.audit_trail') }}">
            <i class="fas fa-shield-halved"></i>
            Journal d’audit
        </a>
    </nav>

    <form method="POST" action="{{ route('logout') }}" class="adm-profile-logout">
        @csrf
        <button type="submit">
            <i class="fas fa-sign-out-alt"></i>
            Se déconnecter
        </button>
    </form>
</aside>

<style>
.adm-profile-overlay{position:fixed;inset:0;z-index:300;display:none;background:rgba(15,23,42,.45);backdrop-filter:blur(2px)}.adm-profile-overlay.is-open{display:block}.adm-profile-drawer{position:fixed;inset:0 0 0 auto;z-index:301;width:min(100vw,360px);display:flex;flex-direction:column;background:#fff;box-shadow:-18px 0 48px rgba(15,23,42,.2);transform:translateX(100%);transition:transform .22s ease}.adm-profile-drawer.is-open{transform:translateX(0)}.adm-profile-head{display:grid;grid-template-columns:46px minmax(0,1fr) 32px;gap:12px;align-items:center;padding:20px 16px;background:var(--adm-accent);color:#fff}.adm-profile-avatar{width:46px;height:46px;display:flex;align-items:center;justify-content:center;border:2px solid rgba(255,255,255,.48);border-radius:50%;background:rgba(255,255,255,.18);font-weight:900}.adm-profile-identity{min-width:0}.adm-profile-identity strong,.adm-profile-identity span,.adm-profile-identity small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.adm-profile-identity strong{font-size:.9rem}.adm-profile-identity span{margin-top:2px;font-size:.7rem;opacity:.78}.adm-profile-identity small{margin-top:2px;font-size:.66rem;opacity:.7}.adm-profile-close{width:32px;height:32px;border:0;border-radius:50%;background:rgba(255,255,255,.15);color:#fff;cursor:pointer}.adm-profile-nav{display:flex;flex-direction:column;padding:10px 0}.adm-profile-nav a{display:flex;align-items:center;gap:10px;padding:12px 20px;color:#374151;text-decoration:none;font-size:.8rem;font-weight:650}.adm-profile-nav a:hover{background:#f0fdf4;color:var(--adm-accent)}.adm-profile-nav i{width:18px;text-align:center}.adm-profile-logout{margin-top:auto;padding:14px;border-top:1px solid #edf2f7}.adm-profile-logout button{width:100%;display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;border:0;border-radius:9px;background:#fef2f2;color:#dc2626;font:700 .78rem 'Poppins',sans-serif;cursor:pointer}.adm-profile-logout button:hover{background:#fee2e2}
</style>

<script>
function admProfileOpen(){var drawer=document.getElementById('admProfileDrawer'),overlay=document.getElementById('admProfileOverlay');if(!drawer||!overlay)return;drawer.classList.add('is-open');overlay.classList.add('is-open');drawer.setAttribute('aria-hidden','false');overlay.setAttribute('aria-hidden','false')}
function admProfileClose(){var drawer=document.getElementById('admProfileDrawer'),overlay=document.getElementById('admProfileOverlay');if(!drawer||!overlay)return;drawer.classList.remove('is-open');overlay.classList.remove('is-open');drawer.setAttribute('aria-hidden','true');overlay.setAttribute('aria-hidden','true')}
document.addEventListener('keydown',function(event){if(event.key==='Escape')admProfileClose()});
</script>
