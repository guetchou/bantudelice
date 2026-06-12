@extends('layouts.driver-modern')
@section('title', 'Support & aide | ' . \App\Services\ConfigService::getCompanyName())
@section('nav_support', 'is-active')
@section('driver_initials', strtoupper(substr($driver->name ?? 'L', 0, 2)))
@section('driver_name', $driver->name ?? 'Livreur')
@section('driver_phone', $driver->phone ?? '')
@section('online_pill_class', ($driver->status ?? 'offline') === 'online' ? '' : 'offline')
@section('online_pill_label', ($driver->status ?? 'offline') === 'online' ? 'En ligne' : 'Hors ligne')
@section('page_title', 'Support & aide')

@section('style')
<style>
.sp-body { padding: 20px 24px 48px; display: flex; flex-direction: column; gap: 16px; }

/* Hero */
.sp-hero {
    background: var(--c-dark);
    border-radius: 14px; padding: 20px 22px;
    display: flex; align-items: center; gap: 14px;
}
.sp-hero-icon {
    width: 48px; height: 48px; border-radius: 12px;
    background: rgba(255,255,255,.07); display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0; color: var(--c-primary);
    border: 1px solid rgba(255,255,255,.08);
}
.sp-hero-title { font-size: 1rem; font-weight: 900; color: #fff; }
.sp-hero-sub   { font-size: .78rem; color: rgba(255,255,255,.45); margin-top: 3px; line-height: 1.5; }

/* Section header */
.sp-sec-title {
    font-size: .75rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .1em; color: var(--c-text-muted);
    display: flex; align-items: center; gap: 6px; margin-bottom: 10px;
}

/* Contacts */
.sp-contacts { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.sp-contact {
    background: var(--c-surface); border: 1px solid var(--c-border); border-radius: 13px;
    padding: 14px 12px; display: flex; flex-direction: column; align-items: center; gap: 8px;
    text-align: center; text-decoration: none; color: inherit;
    transition: border-color .15s, box-shadow .15s;
    cursor: pointer;
}
.sp-contact:hover { border-color: var(--c-primary); box-shadow: 0 4px 16px rgba(255,90,31,.1); }
.sp-contact-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #fff; }
.sp-contact.whatsapp .sp-contact-icon { background: #25d366; }
.sp-contact.phone    .sp-contact-icon { background: var(--c-info); }
.sp-contact.email    .sp-contact-icon { background: var(--c-primary); }
.sp-contact.incident .sp-contact-icon { background: var(--c-err); }
.sp-contact-title { font-size: .8rem; font-weight: 800; color: var(--c-text); }
.sp-contact-sub   { font-size: .68rem; color: var(--c-text-dim); }

/* Card */
.sp-card { background: var(--c-surface); border: 1px solid var(--c-border); border-radius: 14px; overflow: hidden; }
.sp-card-head { padding: 13px 16px; border-bottom: 1px solid var(--c-border); display: flex; align-items: center; gap: 8px; }
.sp-card-head-icon  { font-size: .9rem; color: var(--c-primary); }
.sp-card-head-title { font-size: .85rem; font-weight: 800; color: var(--c-text); }

/* Status */
.sp-status { display: flex; align-items: center; gap: 12px; padding: 14px 16px; }
.sp-status-dot  { width: 11px; height: 11px; border-radius: 50%; background: var(--c-green-lt); flex-shrink: 0; animation: spPulse 2s infinite; }
@keyframes spPulse { 0%{box-shadow:0 0 0 0 rgba(34,197,94,.5)} 70%{box-shadow:0 0 0 7px rgba(34,197,94,0)} 100%{box-shadow:0 0 0 0 rgba(34,197,94,0)} }
.sp-status-title { font-size: .83rem; font-weight: 800; color: var(--c-text); }
.sp-status-sub   { font-size: .7rem; color: var(--c-text-muted); margin-top: 2px; }

/* FAQ */
.sp-faq-item { border-bottom: 1px solid var(--c-bg); }
.sp-faq-item:last-child { border-bottom: none; }
.sp-faq-q {
    display: flex; align-items: center; justify-content: space-between;
    padding: 13px 16px; cursor: pointer; user-select: none;
    font-size: .82rem; font-weight: 700; color: var(--c-text);
    transition: color .15s;
}
.sp-faq-q:hover { color: var(--c-primary); }
.sp-faq-q .sp-faq-chevron { transition: transform .2s; flex-shrink: 0; color: var(--c-text-dim); font-size: .75rem; }
.sp-faq-q.open .sp-faq-chevron { transform: rotate(180deg); }
.sp-faq-a { max-height: 0; overflow: hidden; transition: max-height .25s ease; }
.sp-faq-a.open { max-height: 300px; }
.sp-faq-a-inner { padding: 0 16px 13px; font-size: .78rem; color: var(--c-text-2); line-height: 1.65; }

/* Compte */
.sp-account-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 11px 16px; border-bottom: 1px solid var(--c-bg);
    font-size: .82rem;
}
.sp-account-row:last-of-type { border-bottom: none; }
.sp-account-lbl { font-weight: 600; color: var(--c-text-muted); }
.sp-account-val { font-weight: 800; color: var(--c-text); }

@media (max-width: 768px) { .sp-body { padding: 14px 14px 40px; } }
</style>
@endsection

@section('content')
<div class="sp-body">

    {{-- ── HERO ── --}}
    <div class="sp-hero">
        <div class="sp-hero-icon"><i class="fas fa-headset"></i></div>
        <div>
            <div class="sp-hero-title">Support BantuDelice</div>
            <div class="sp-hero-sub">Nous sommes là 7j/7 &mdash; réponse en moins de 15 min en journée.</div>
        </div>
    </div>

    {{-- ── CONTACTS ── --}}
    <div>
        <div class="sp-sec-title"><i class="fas fa-headset" style="color:var(--c-primary);"></i> Nous contacter</div>
        <div class="sp-contacts">
            <a href="https://wa.me/242060000000" target="_blank" class="sp-contact whatsapp">
                <div class="sp-contact-icon"><i class="fab fa-whatsapp"></i></div>
                <div class="sp-contact-title">WhatsApp</div>
                <div class="sp-contact-sub">Réponse rapide &middot; 7j/7</div>
            </a>
            <a href="tel:+242060000000" class="sp-contact phone">
                <div class="sp-contact-icon"><i class="fas fa-phone"></i></div>
                <div class="sp-contact-title">Appel direct</div>
                <div class="sp-contact-sub">Lun&ndash;Sam &middot; 8h&ndash;20h</div>
            </a>
            <a href="mailto:livreurs@bantudelice.com" class="sp-contact email">
                <div class="sp-contact-icon"><i class="fas fa-envelope"></i></div>
                <div class="sp-contact-title">Email support</div>
                <div class="sp-contact-sub">Réponse sous 24h</div>
            </a>
            <a href="{{ route('driver.deliveries') }}" class="sp-contact incident">
                <div class="sp-contact-icon"><i class="fas fa-triangle-exclamation"></i></div>
                <div class="sp-contact-title">Signaler incident</div>
                <div class="sp-contact-sub">Via votre livraison active</div>
            </a>
        </div>
    </div>

    {{-- ── ÉTAT DU SERVICE ── --}}
    <div class="sp-card">
        <div class="sp-card-head">
            <span class="sp-card-head-icon"><i class="fas fa-server"></i></span>
            <span class="sp-card-head-title">État du service</span>
        </div>
        <div class="sp-status">
            <span class="sp-status-dot"></span>
            <div>
                <div class="sp-status-title">Plateforme opérationnelle</div>
                <div class="sp-status-sub">Tous les systèmes fonctionnent normalement &middot; {{ now()->format('d/m à H:i') }}</div>
            </div>
        </div>
    </div>

    {{-- ── FAQ ── --}}
    <div class="sp-card">
        <div class="sp-card-head">
            <span class="sp-card-head-icon"><i class="fas fa-circle-question"></i></span>
            <span class="sp-card-head-title">Questions fréquentes</span>
        </div>
        @php $faqs = [
            ['q'=>"Comment passer en ligne / hors ligne ?",         'a'=>"Sur le tableau de bord, utilisez le bouton « Passer en ligne » en haut à droite. Quand vous êtes en ligne, votre position GPS est partagée et vous pouvez recevoir des missions."],
            ['q'=>"Je ne reçois pas de missions, pourquoi ?",       'a'=>"Vérifiez que vous êtes bien en ligne (indicateur vert). Assurez-vous que le GPS de votre téléphone est activé. Si le problème persiste, contactez le support via WhatsApp."],
            ['q'=>"Comment signaler un incident pendant une livraison ?", 'a'=>"Sur la carte de la livraison concernée, cliquez sur « Signaler un problème ». Choisissez le motif et ajoutez des détails. Le support sera notifié immédiatement."],
            ['q'=>"Quand et comment suis-je payé ?",                'a'=>"Vos gains s'accumulent dans la section « Mes gains ». Le paiement se fait selon le cycle convenu avec BantuDelice (hebdomadaire ou mensuel)."],
            ['q'=>"Que faire si le client est absent à la livraison ?", 'a'=>"Essayez d'appeler le client (bouton dans les détails de la commande). S'il ne répond pas après 5 minutes, signalez l'incident « Client absent »."],
            ['q'=>"Comment fonctionne le code OTP ?",               'a'=>"Le code OTP est communiqué par le client à la remise pour confirmer que vous lui avez bien remis sa commande. Sans ce code, utilisez la photo de preuve."],
        ]; @endphp
        @foreach($faqs as $i => $faq)
        <div class="sp-faq-item">
            <div class="sp-faq-q" onclick="spToggleFaq(this)" id="faq-q-{{ $i }}">
                <span>{{ $faq['q'] }}</span>
                <i class="fas fa-chevron-down sp-faq-chevron"></i>
            </div>
            <div class="sp-faq-a" id="faq-a-{{ $i }}">
                <div class="sp-faq-a-inner">{{ $faq['a'] }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── MON COMPTE ── --}}
    <div class="sp-card">
        <div class="sp-card-head">
            <span class="sp-card-head-icon"><i class="fas fa-user"></i></span>
            <span class="sp-card-head-title">Mon compte</span>
        </div>
        <div class="sp-account-row">
            <span class="sp-account-lbl">Nom</span>
            <span class="sp-account-val">{{ $driver->name ?? '—' }}</span>
        </div>
        <div class="sp-account-row">
            <span class="sp-account-lbl">Téléphone</span>
            <span class="sp-account-val">{{ $driver->phone ?? '—' }}</span>
        </div>
        <div class="sp-account-row">
            <span class="sp-account-lbl">Email</span>
            <span class="sp-account-val">{{ $driver->email ?? '—' }}</span>
        </div>
        <div class="sp-account-row">
            <span class="sp-account-lbl">Statut</span>
            <span class="sp-account-val" style="color:{{ ($driver->status??'offline')==='online' ? 'var(--c-green-lt)' : 'var(--c-text-dim)' }};">
                {{ ($driver->status ?? 'offline') === 'online' ? 'En ligne' : 'Hors ligne' }}
            </span>
        </div>
        <div style="padding: 12px 16px; border-top: 1px solid var(--c-border);">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="display:inline-flex;align-items:center;gap:7px;border:1px solid rgba(239,68,68,.3);background:rgba(239,68,68,.06);border-radius:8px;padding:8px 16px;font-size:.8rem;font-weight:700;color:#b91c1c;cursor:pointer;font-family:var(--font-body);">
                    <i class="fas fa-arrow-right-from-bracket"></i> Se déconnecter
                </button>
            </form>
        </div>
    </div>

</div>
@endsection

@section('script')
<script>
function spToggleFaq(btn) {
    var idx    = btn.id.replace('faq-q-','');
    var answer = document.getElementById('faq-a-' + idx);
    var isOpen = btn.classList.toggle('open');
    answer.classList.toggle('open', isOpen);
}
</script>
@endsection
