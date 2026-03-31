@extends('frontend.layouts.app-modern')
@section('title', 'Commander un Taxi | BantuDelice')
@section('description', 'Commander un taxi au Congo avec une page transport dediee, sans melanger ce parcours avec le food checkout BantuDelice.')

@php
    $pricingData = [
        'base_fare' => (float) ($pricing->base_fare ?? 500),
        'price_per_km' => (float) ($pricing->price_per_km ?? 200),
        'price_per_minute' => (float) ($pricing->price_per_minute ?? 50),
        'minimum_fare' => (float) ($pricing->minimum_fare ?? 1000),
        'surge_multiplier' => (float) ($pricing->surge_multiplier ?? 1),
    ];

    $rideOptions = [
        [
            'key' => 'eco',
            'name' => 'Eco',
            'description' => 'Trajet du quotidien, prix le plus leger',
            'multiplier' => 1,
            'icon' => 'fas fa-taxi',
            'base_label' => 'des ' . number_format(max(round(($pricingData['minimum_fare'] ?? 2500)), 2500), 0, ',', ' ') . ' F',
        ],
        [
            'key' => 'comfort',
            'name' => 'Confort',
            'description' => 'Plus d espace et prise en charge plus douce',
            'multiplier' => 1.18,
            'icon' => 'fas fa-star',
            'base_label' => 'des ' . number_format(max(round(($pricingData['minimum_fare'] ?? 2500) * 1.18), 4000), 0, ',', ' ') . ' F',
        ],
        [
            'key' => 'xl',
            'name' => 'XL',
            'description' => 'Ideal si vous etes plusieurs ou avec bagages',
            'multiplier' => 1.35,
            'icon' => 'fas fa-users',
            'base_label' => 'des ' . number_format(max(round(($pricingData['minimum_fare'] ?? 2500) * 1.35), 5500), 0, ',', ' ') . ' F',
        ],
    ];

    $faqUrl = route('faq');
    $offersUrl = route('offers');
    $helpUrl = route('help');
    $contactUrl = route('contact.us');
    $aboutUrl = route('about.us');
    $driverUrl = route('driver');
    $partnerUrl = route('partner');
    $legalUrl = route('legal.notices');
    $cookiesUrl = route('cookies.policy');
    $privacyUrl = route('privacy.policy');
    $foodEnabled = (bool) config('bantudelice_modules.food.enabled', true);
    $colisEnabled = (bool) config('bantudelice_modules.colis.enabled', true);
    $transportEnabled = (bool) config('bantudelice_modules.transport.enabled', true);
    $colisUrl = $colisEnabled ? route('colis.landing') : null;
    $restaurantsUrl = $foodEnabled ? route('restaurants.all') : null;
    $taxiUrl = route('transport.taxi');
    $carpoolUrl = route('transport.carpool');
    $rentalUrl = route('transport.rental');
    $busUrl = route('transport.bus');
    $trackOrderUrl = $foodEnabled ? route('track.order') : null;
@endphp

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@700;800;900&display=swap" rel="stylesheet">
<style>
    .modern-header,
    .modern-footer { display:none !important; }

    body.bd-future-shell {
        background:#0f1110;
        color:#f5f7f1;
        font-family:'Segoe UI', system-ui, sans-serif;
        cursor:none;
    }

    body.bd-future-shell main { overflow:visible; }

    .taxi-v1 {
        --ink:#111111;
        --ink-2:#171a17;
        --ink-3:#1f231f;
        --ink-4:#272c27;
        --green:#009543;
        --green-soft:rgba(0,149,67,.14);
        --green-border:rgba(0,149,67,.32);
        --orange:#16a34a;
        --orange-soft:rgba(22,163,74,.12);
        --orange-border:rgba(22,163,74,.26);
        --paper:#ffffff;
        --text:#eef2e9;
        --text-2:#a5ad9d;
        --text-3:#66705f;
        --line:rgba(255,255,255,.08);
        --line-2:rgba(255,255,255,.16);
        --shadow:0 24px 70px rgba(0,0,0,.45);
        background:var(--ink);
        color:var(--text);
        overflow-x:hidden;
        -webkit-font-smoothing:antialiased;
    }

    .taxi-v1 * { box-sizing:border-box; }
    .taxi-wrap { max-width:1140px; margin:0 auto; padding:0 2.25rem; }

    #taxiCursor { position:fixed; inset:0; z-index:9999; pointer-events:none; }
    #taxiCursorDot { width:6px; height:6px; background:var(--orange); border-radius:50%; position:absolute; transform:translate(-50%,-50%); }
    #taxiCursorRing { width:28px; height:28px; border:1px solid rgba(22,163,74,.45); border-radius:50%; position:absolute; transform:translate(-50%,-50%); transition:width .2s,height .2s,border-color .2s,transform .18s cubic-bezier(.25,.46,.45,.94); }
    body:has(.taxi-v1 a:hover) #taxiCursorRing,
    body:has(.taxi-v1 button:hover) #taxiCursorRing { width:42px; height:42px; border-color:var(--orange); }

    body.bd-future-shell::before{
        content:'';position:fixed;inset:0;z-index:9998;pointer-events:none;
        background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
        opacity:.018;
    }

    .taxi-wa {
        position:fixed;bottom:2rem;right:2rem;z-index:200;width:50px;height:50px;border-radius:50%;
        background:#25d366;display:flex;align-items:center;justify-content:center;color:#fff;
        box-shadow:0 4px 20px rgba(37,211,102,.35);animation:taxiWaFloat 3.5s ease-in-out infinite;
    }
    .taxi-wa svg { width:22px; height:22px; }
    @keyframes taxiWaFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-5px)} }

    .taxi-ticker { background:var(--green); height:32px; overflow:hidden; display:flex; align-items:center; position:relative; z-index:100; }
    .taxi-ticker__track { display:flex; white-space:nowrap; animation:taxiTicker 28s linear infinite; }
    .taxi-ticker__item { display:inline-flex; align-items:center; gap:12px; padding:0 2.5rem; font-size:.75rem; font-weight:800; color:#fff; letter-spacing:.18em; text-transform:uppercase; }
    .taxi-ticker__sep { width:5px; height:5px; background:#fff; border-radius:50%; opacity:.5; }
    @keyframes taxiTicker { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }

    .taxi-nav {
        position:fixed; top:32px; left:0; right:0; z-index:180;
        display:flex; align-items:center; justify-content:space-between; gap:1rem;
        padding:.95rem 3rem; background:rgba(17,17,17,.95); backdrop-filter:blur(24px);
        border-bottom:1px solid var(--line); transition:padding .3s;
    }
    .taxi-nav.is-compact { padding:.68rem 3rem; }
    .taxi-nav__logo { display:flex; align-items:center; gap:.7rem; font-size:1.45rem; font-weight:900; color:#fff; letter-spacing:-.04em; text-decoration:none; }
    .taxi-nav__logo-word { color:var(--green); }
    .taxi-nav__logo-dot { width:10px; height:10px; background:var(--orange); border-radius:50%; box-shadow:0 0 12px rgba(22,163,74,.35); margin-top:-12px; }
    .taxi-nav__links, .taxi-nav__end { display:flex; align-items:center; gap:.15rem; }
    .taxi-nav__links a { padding:.4rem .95rem; font-size:.78rem; font-weight:600; color:var(--text-2); border-radius:4px; transition:.15s; text-decoration:none; }
    .taxi-nav__links a:hover { color:#fff; background:var(--ink-4); }
    .taxi-nav__links a.is-active { color:var(--orange); background:var(--orange-soft); }
    .taxi-nav__lang { font-size:.7rem; color:var(--text-3); padding:.3rem .6rem; border:1px solid var(--line-2); border-radius:3px; text-decoration:none; }
    .taxi-nav__cta { background:var(--orange); color:#fff; padding:.6rem 1.4rem; border-radius:4px; font-size:.82rem; font-weight:900; text-transform:uppercase; letter-spacing:.06em; text-decoration:none; box-shadow:0 2px 16px rgba(22,163,74,.24); }

    .taxi-hero {
        min-height:88vh; position:relative; overflow:hidden; display:flex; align-items:flex-end;
        padding-bottom:0;
        background:
            linear-gradient(90deg, rgba(8,10,9,.84) 0%, rgba(8,10,9,.72) 28%, rgba(8,10,9,.22) 56%, rgba(8,10,9,.1) 100%),
            linear-gradient(180deg, rgba(15,17,16,.12) 0%, rgba(17,20,18,.62) 100%),
            url('/images/ai/taxi_brazzaville.png') 64% center / cover no-repeat;
    }
    .taxi-hero__grid-bg {
        position:absolute; inset:0; z-index:0;
        background-image:linear-gradient(rgba(0,149,67,.035) 1px,transparent 1px), linear-gradient(90deg,rgba(22,163,74,.03) 1px,transparent 1px);
        background-size:60px 60px;
    }
    .taxi-hero__stripe, .taxi-hero__stripe-2 {
        position:absolute; top:-10%; right:18%; width:2px; height:130%; transform:rotate(12deg); z-index:1;
        background:linear-gradient(to bottom,transparent,rgba(22,163,74,.12),transparent);
    }
    .taxi-hero__stripe-2 { right:24%; width:1px; background:linear-gradient(to bottom,transparent,rgba(0,149,67,.10),transparent); }
    .taxi-hero__num {
        position:absolute; right:3rem; top:50%; transform:translateY(-50%);
        font-size:28vw; font-weight:900; color:rgba(0,149,67,.05); line-height:1; user-select:none; z-index:0; letter-spacing:-.06em;
    }
    .taxi-hero__inner {
        position:relative; z-index:2; width:100%; max-width:1200px; margin:0 auto;
        padding:8.1rem 2rem 0; display:grid; grid-template-columns:minmax(0, 780px); gap:1.5rem; align-items:end;
    }
    .taxi-hero__content { max-width:780px; }
    .taxi-brand-mark {
        display:flex; align-items:flex-end; gap:.85rem; margin-bottom:1.05rem;
    }
    .taxi-brand-mark__word {
        font-size:clamp(1.8rem, 3.8vw, 3.2rem); line-height:.9; font-weight:900; letter-spacing:-.05em;
        text-transform:uppercase; color:#fff; font-style:italic;
    }
    .taxi-brand-mark__word strong { color:var(--green); }
    .taxi-brand-mark__dot {
        width:14px; height:14px; border-radius:50%; background:var(--orange); box-shadow:0 0 0 10px rgba(22,163,74,.12);
        margin-bottom:.55rem; flex-shrink:0;
    }
    .taxi-brand-mark__meta {
        display:flex; gap:.45rem; flex-wrap:wrap; margin-bottom:.95rem;
    }
    .taxi-brand-mark__chip {
        display:inline-flex; align-items:center; gap:.38rem; padding:.34rem .58rem; border-radius:999px;
        border:1px solid rgba(255,255,255,.14); background:rgba(255,255,255,.06); color:#eff4ee;
        font-size:.58rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase;
    }
    .taxi-brand-mark__chip::before {
        content:''; width:8px; height:8px; border-radius:50%; background:var(--green);
        box-shadow:0 0 0 4px rgba(0,149,67,.18);
    }
    .taxi-rv { opacity:0; transform:translateY(20px); animation:taxiReveal .7s cubic-bezier(.22,1,.36,1) forwards; }
    .taxi-rv1 { animation-delay:.05s; } .taxi-rv2 { animation-delay:.15s; } .taxi-rv3 { animation-delay:.28s; } .taxi-rv4 { animation-delay:.42s; } .taxi-rv5 { animation-delay:.56s; }
    @keyframes taxiReveal { to { opacity:1; transform:translateY(0); } }
    .taxi-label {
        display:inline-flex; align-items:center; gap:10px; font-size:.58rem; letter-spacing:.22em;
        color:var(--green); text-transform:uppercase; margin-bottom:1.2rem;
    }
    .taxi-label::before { content:''; width:24px; height:1px; background:var(--orange); }
    .taxi-title {
        font-size:clamp(3.6rem,7vw,6rem); line-height:.9; letter-spacing:-.03em;
        text-transform:uppercase; color:#fff; margin:0; font-weight:900; font-style:italic;
    }
    .taxi-title .accent { color:var(--orange); }
    .taxi-title .line-2 { display:block; -webkit-text-stroke:1px rgba(255,255,255,.28); color:transparent; }
    .taxi-sub { font-size:.9rem; color:#eef3ee; line-height:1.68; max-width:560px; margin:1rem 0 1.5rem; font-weight:300; }
    .taxi-formula { display:flex; gap:.38rem; margin-bottom:1.35rem; }
    .taxi-formula__btn {
        flex:1; padding:.66rem 0; border-radius:4px; border:1px solid var(--line-2); background:transparent; color:var(--text-2);
        font-size:.72rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase; transition:.2s;
    }
    .taxi-formula__btn:hover { border-color:var(--orange-border); color:#fff; }
    .taxi-formula__btn.is-active { background:var(--orange); color:#fff; border-color:var(--orange); box-shadow:0 4px 20px rgba(22,163,74,.25); }
    .taxi-formula__btn span { display:block; font-size:.56rem; margin-top:.15rem; opacity:.75; }
    .taxi-stats { display:flex; gap:1.6rem; margin-bottom:1.55rem; }
    .taxi-stats__num { font-size:1.8rem; font-weight:900; color:var(--green); line-height:1; display:block; }
    .taxi-stats__lbl { font-size:.6rem; color:var(--text-3); text-transform:uppercase; letter-spacing:.12em; font-weight:600; margin-top:.18rem; }

    .taxi-book-panel {
        background:rgba(255,255,255,.96); border:1px solid rgba(255,255,255,.88); border-radius:22px; overflow:hidden;
        box-shadow:0 18px 42px rgba(0,0,0,.16); align-self:start; display:flex; flex-direction:column; margin:0 0 1.25rem; color:#111;
        position:relative;
        height:auto;
        width:100%;
        max-width:760px;
        margin-left:0;
        backdrop-filter:blur(10px);
        -webkit-backdrop-filter:blur(10px);
    }
    .taxi-book-panel::before {
        content:''; position:absolute; inset:0 0 auto 0; height:4px;
        background:linear-gradient(90deg,var(--green) 0%, var(--orange) 100%);
    }
    .taxi-book-panel__header {
        background:#fff; color:#111; padding:.75rem 1rem .4rem; display:flex; align-items:center; justify-content:space-between;
    }
    .taxi-book-panel__title { font-size:.76rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#1f2a22; }
    .taxi-book-panel__status { display:flex; align-items:center; gap:5px; font-size:.5rem; opacity:.9; text-transform:uppercase; letter-spacing:.1em; color:#5f6a61; }
    .taxi-book-panel__status span { width:6px; height:6px; background:var(--green); border-radius:50%; animation:taxiBlink 2s ease-in-out infinite; }
    @keyframes taxiBlink { 0%,100%{opacity:1} 50%{opacity:.3} }
    .taxi-book-panel__body { padding:.45rem 1rem 1rem; display:flex; flex-direction:column; gap:.65rem; }
    .taxi-book-panel__body > div { padding:.2rem 0; border-bottom:none; }
    .taxi-book-panel__body > div:last-of-type { border-bottom:none; }
    .taxi-book-grid { display:grid; grid-template-columns:minmax(0,1.3fr) minmax(0,1.15fr) auto; gap:.7rem; align-items:end; }
    .taxi-book-grid--tight { grid-template-columns:1fr 1fr; gap:.55rem; }
    .taxi-book-col { min-width:0; }
    .taxi-book-col--pickup {
        padding:0; border-radius:0;
        background:transparent;
        border:none;
        box-shadow:none;
        backdrop-filter:none;
        -webkit-backdrop-filter:none;
    }
    .taxi-stage-kicker {
        display:inline-flex; align-items:center; gap:.45rem; margin-bottom:.2rem; padding:.34rem .52rem;
        border-radius:999px; background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.22); color:#f4fff8;
        font-size:.52rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase;
    }
    .taxi-stage-kicker::before {
        content:''; width:7px; height:7px; border-radius:50%; background:#8dffbc; box-shadow:0 0 0 4px rgba(141,255,188,.16);
    }
    .taxi-stage-destination { display:none !important; }
    .taxi-stage-destination.is-visible { display:block; }
    .taxi-progressive-section { display:none !important; }
    .taxi-progressive-section.is-visible { display:block !important; }
    .taxi-book-summary.taxi-progressive-section.is-visible { display:grid !important; }
    .taxi-book-grid--tight.taxi-progressive-section.is-visible { display:grid !important; }
    .taxi-map-grid.taxi-progressive-section.is-visible { display:grid !important; }
    .taxi-confirm.taxi-progressive-section.is-visible { display:flex !important; }
    .taxi-progressive-grid { display:none !important; gap:.56rem; }
    .taxi-progressive-grid.is-visible { display:grid !important; }
    .taxi-map-placeholder {
        display:flex; align-items:center; justify-content:center; min-height:88px;
        border:1px dashed rgba(17,17,17,.12); border-radius:18px; background:#f6f8f4; color:#6f786d;
        font-size:.72rem; line-height:1.45; text-align:center; padding:.8rem 1rem;
    }
    .taxi-map-placeholder.is-hidden { display:none !important; }
    .taxi-field-label { display:block; font-size:.52rem; letter-spacing:.16em; color:#4a584d; text-transform:uppercase; margin-bottom:.42rem; font-weight:800; }
    .taxi-input-wrap {
        display:flex; align-items:center; gap:.8rem; background:#fff; border:1px solid rgba(17,17,17,.12); border-radius:12px; padding:.92rem 1rem; position:relative;
        min-height:58px;
    }
    .taxi-input-wrap:focus-within { border-color:rgba(0,149,67,.34); box-shadow:0 0 0 3px rgba(0,149,67,.08); }
    .taxi-input-icon { flex-shrink:0; opacity:.8; }
    .taxi-input-icon svg { width:16px; height:16px; }
    #pickupInput, #dropoffInput, #scheduledAtInput, #passengerCount {
        flex:1; background:transparent; border:none; outline:none; font-size:.94rem; color:#111; font-family:inherit; width:100%;
    }
    #pickupInput::placeholder, #dropoffInput::placeholder, #scheduledAtInput::placeholder { color:#97a092; }
    .taxi-locate-btn {
        background:#f3f6f3; border:1px solid rgba(17,17,17,.08); color:var(--green); font-size:.64rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; flex-shrink:0;
        border-radius:10px; padding:.72rem .92rem; min-height:42px;
    }
    .taxi-book-search-cta {
        display:inline-flex; align-items:center; justify-content:center; align-self:stretch;
        min-width:150px; min-height:58px; padding:0 1.1rem; border:none; border-radius:12px;
        background:var(--orange); color:#fff; font-size:.74rem; font-weight:900; letter-spacing:.06em; text-transform:uppercase;
        box-shadow:0 8px 22px rgba(22,163,74,.22); cursor:pointer; transition:.2s;
    }
    .taxi-book-search-cta:hover { background:#16a34a; transform:translateY(-1px); }
    .taxi-hero-search-meta { display:flex; align-items:center; gap:.9rem; flex-wrap:wrap; margin:-.2rem 0 1.3rem; }
    .taxi-hero-search-link { font-size:.72rem; color:#eef3ee; text-decoration:none; border-bottom:1px solid rgba(255,255,255,.32); }
    .taxi-hero-search-link:hover { color:#fff; border-bottom-color:#fff; }
    .taxi-hero-search-note { font-size:.7rem; color:rgba(255,255,255,.7); }
    .taxi-arrow-row { display:flex; align-items:center; gap:.6rem; padding:0 .1rem; }
    .taxi-arrow-row__line { flex:1; height:1px; background:rgba(17,17,17,.08); }
    .taxi-arrow-row__icon {
        width:20px; height:20px; border-radius:50%; background:#f6f8f4; border:1px solid rgba(17,17,17,.06);
        display:flex; align-items:center; justify-content:center; color:#738072;
    }
    .taxi-book-summary { display:grid; grid-template-columns:1fr 1fr; gap:.35rem; }
    .taxi-book-summary__item { background:#f8f9f6; border:1px solid rgba(17,17,17,.05); border-radius:4px; padding:.46rem .58rem; }
    .taxi-book-summary__key { font-size:.46rem; color:#7a8375; letter-spacing:.08em; text-transform:uppercase; margin-bottom:.12rem; }
    .taxi-book-summary__val { font-size:.8rem; font-weight:800; color:#111; }
    .taxi-book-summary__val.is-green { color:var(--green); }
    .taxi-mode-row, .taxi-pay-row { display:flex; gap:.4rem; padding:.18rem; border-radius:999px; background:#f5f7f4; border:1px solid rgba(17,17,17,.05); }
    .taxi-mode-row label, .taxi-pay-row label {
        flex:1; display:flex; align-items:center; justify-content:center; padding:.52rem .42rem; border-radius:999px;
        border:1px solid transparent; background:transparent; color:#66705f; font-size:.64rem; font-weight:800; text-transform:uppercase; letter-spacing:.03em; cursor:pointer;
    }
    .taxi-mode-row input, .taxi-pay-row input { display:none; }
    .taxi-mode-row label.is-selected, .taxi-pay-row label.is-selected { background:#fff; border-color:rgba(0,149,67,.14); color:var(--green); box-shadow:0 4px 10px rgba(17,17,17,.04); }
    .taxi-confirm {
        background:var(--orange); color:#fff; padding:.72rem; border-radius:999px; font-size:.76rem; font-weight:900; letter-spacing:.07em; text-transform:uppercase; border:none;
        display:flex; align-items:center; justify-content:center; gap:.55rem; width:100%; margin-top:auto; box-shadow:0 4px 18px rgba(22,163,74,.16); transition:.22s;
    }
    .taxi-confirm:hover:not(:disabled) { background:#16a34a; transform:translateY(-2px); }
    .taxi-confirm:disabled { opacity:.55; cursor:not-allowed; }
    .taxi-confirm__price {
        background:rgba(255,255,255,.16); border-radius:3px; padding:.16rem .48rem; font-size:.7rem; font-weight:700;
    }
    .taxi-options-grid { display:grid; grid-template-columns:1.2fr .8fr; gap:.75rem; align-items:start; }
    .taxi-options-stack { display:grid; grid-template-columns:1fr; gap:.72rem; }

    .taxi-hero-bottom {
        position:relative; z-index:2; width:100%; border-top:1px solid var(--line); display:flex; align-items:stretch;
    }
    .taxi-hero-bottom__item { flex:1; padding:.82rem 1.6rem; display:flex; align-items:center; gap:.72rem; border-right:1px solid var(--line); font-size:.7rem; color:var(--text-2); }
    .taxi-hero-bottom__item:last-child { border-right:none; }
    .taxi-hero-bottom__icon {
        width:36px; height:36px; border-radius:4px; background:var(--ink-3); border:1px solid var(--line-2); display:flex; align-items:center; justify-content:center; color:var(--orange); flex-shrink:0;
    }
    .taxi-hero-bottom__icon svg { width:16px; height:16px; }
    .taxi-hero-bottom__label { font-size:.72rem; font-weight:800; color:#fff; letter-spacing:.06em; text-transform:uppercase; margin-bottom:.15rem; }

    .taxi-section { padding:5.5rem 0; position:relative; overflow:hidden; }
    .taxi-section-label {
        display:inline-flex; align-items:center; gap:10px; margin-bottom:1rem; font-size:.62rem; color:var(--green);
        letter-spacing:.22em; text-transform:uppercase;
    }
    .taxi-section-label::before { content:''; width:24px; height:1px; background:var(--orange); }
    .taxi-section-title {
        font-size:clamp(2.3rem,4.2vw,4.2rem); font-weight:900; text-transform:uppercase; letter-spacing:-.01em; line-height:.94; color:#fff; margin-bottom:1rem;
    }
    .taxi-section-title em { font-style:italic; color:var(--orange); }

    .taxi-formules { background:var(--ink); }
    .taxi-formules::before {
        content:'FORMULES'; position:absolute; left:-2rem; top:50%; transform:translateY(-50%) rotate(-90deg);
        font-size:4rem; font-weight:900; color:rgba(0,149,67,.06); letter-spacing:.1em; pointer-events:none; white-space:nowrap;
    }
    .taxi-formules__grid {
        display:grid; grid-template-columns:repeat(3,1fr); gap:1px; background:var(--line); border:1px solid var(--line); border-radius:6px; overflow:hidden; margin-top:2.2rem;
    }
    .taxi-formule-card { background:var(--ink-2); padding:1.65rem 1.35rem; position:relative; overflow:hidden; transition:background .25s; }
    .taxi-formule-card:hover { background:var(--ink-3); }
    .taxi-formule-card.is-featured { background:var(--green); }
    .taxi-formule-card.is-featured:hover { background:#00a34a; }
    .taxi-formule-card__num { position:absolute; top:.8rem; right:1rem; font-size:4rem; font-weight:900; color:rgba(255,255,255,.06); line-height:1; }
    .taxi-formule-card__tag {
        display:inline-block; font-size:.52rem; letter-spacing:.16em; text-transform:uppercase; padding:3px 8px; border-radius:2px; margin-bottom:1rem;
        background:var(--orange-soft); color:var(--orange); border:1px solid var(--orange-border);
    }
    .taxi-formule-card.is-featured .taxi-formule-card__tag { background:rgba(255,255,255,.14); color:#fff; border-color:rgba(255,255,255,.18); }
    .taxi-formule-card__name { font-size:1.95rem; font-weight:900; text-transform:uppercase; letter-spacing:-.01em; color:#fff; margin-bottom:.28rem; line-height:1; }
    .taxi-formule-card__desc { font-size:.74rem; color:var(--text-2); line-height:1.55; margin-bottom:1rem; font-weight:300; }
    .taxi-formule-card.is-featured .taxi-formule-card__desc { color:rgba(255,255,255,.82); }
    .taxi-formule-card__price { font-size:1.55rem; font-weight:800; color:var(--orange); line-height:1; margin-bottom:.24rem; }
    .taxi-formule-card.is-featured .taxi-formule-card__price { color:#fff; }
    .taxi-formule-card__sub { font-size:.54rem; color:var(--text-3); letter-spacing:.07em; text-transform:uppercase; }
    .taxi-formule-card.is-featured .taxi-formule-card__sub { color:rgba(255,255,255,.6); }
    .taxi-formule-card__features { list-style:none; margin-top:.9rem; display:flex; flex-direction:column; gap:.42rem; }
    .taxi-formule-card__features li { display:flex; align-items:center; gap:.55rem; font-size:.7rem; color:var(--text-2); }
    .taxi-formule-card.is-featured .taxi-formule-card__features li { color:rgba(255,255,255,.82); }
    .taxi-formule-card__check { width:15px; height:15px; border-radius:50%; background:var(--green-soft); display:flex; align-items:center; justify-content:center; color:var(--green); flex-shrink:0; }
    .taxi-formule-card.is-featured .taxi-formule-card__check { background:rgba(255,255,255,.16); color:#fff; }
    .taxi-formule-card__check svg { width:8px; height:8px; }
    .taxi-formule-card__cta { display:inline-flex; align-items:center; gap:.5rem; margin-top:1.2rem; font-size:.72rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase; color:var(--orange); transition:gap .2s; }
    .taxi-formule-card.is-featured .taxi-formule-card__cta { color:#fff; }
    .taxi-formule-card:hover .taxi-formule-card__cta { gap:.9rem; }
    .taxi-formule-card__cta svg { width:14px; height:14px; }

    .taxi-howto { background:var(--ink-2); border-top:1px solid var(--line); }
    .taxi-howto__grid { display:grid; grid-template-columns:1fr 1fr; gap:2.6rem; align-items:center; margin-top:2.2rem; }
    .taxi-howto__steps { display:flex; flex-direction:column; }
    .taxi-howto__step { display:flex; gap:1.1rem; align-items:flex-start; padding:1rem 0; border-bottom:1px solid var(--line); transition:padding-left .25s; }
    .taxi-howto__step:hover { padding-left:.5rem; }
    .taxi-howto__step:last-child { border-bottom:none; }
    .taxi-howto__num { font-size:2.2rem; font-weight:900; color:rgba(22,163,74,.14); line-height:1; flex-shrink:0; width:40px; transition:color .25s; }
    .taxi-howto__step:hover .taxi-howto__num { color:rgba(22,163,74,.32); }
    .taxi-howto__title { font-size:.96rem; font-weight:800; text-transform:uppercase; letter-spacing:.03em; color:#fff; margin-bottom:.3rem; }
    .taxi-howto__desc { font-size:.72rem; color:var(--text-2); line-height:1.55; font-weight:300; }
    .taxi-howto__visual { background:#fcfcfa; color:#111; border:1px solid rgba(17,17,17,.08); border-radius:8px; padding:.85rem; position:relative; overflow:hidden; box-shadow:0 14px 28px rgba(0,0,0,.16); }
    .taxi-howto__visual::before { content:''; position:absolute; top:-40px; right:-40px; width:220px; height:220px; border-radius:50%; background:radial-gradient(circle,rgba(0,149,67,.08),transparent 70%); }
    .taxi-map-head { display:flex; align-items:center; justify-content:space-between; gap:.8rem; margin-bottom:.85rem; }
    .taxi-map-head__title { font-size:.88rem; font-weight:900; letter-spacing:.07em; text-transform:uppercase; color:#111; }
    .taxi-geostate {
        display:inline-flex; align-items:center; gap:6px; padding:.36rem .58rem; border-radius:999px;
        background:#f5f7f4; border:1px solid rgba(17,17,17,.08); color:#5f6a5f; font-size:.58rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase;
    }
    .taxi-geostate[data-state="success"] { color:var(--green); border-color:rgba(0,149,67,.18); background:rgba(0,149,67,.06); }
    .taxi-geostate[data-state="error"] { color:var(--orange); border-color:rgba(22,163,74,.18); background:rgba(22,163,74,.06); }
    .taxi-map-box { border-radius:8px; overflow:hidden; border:1px solid rgba(17,17,17,.08); background:#edf2ec; min-height:250px; }
    #taxiMap { width:100%; height:250px; z-index:1; }
    #taxiMap .leaflet-control-container { z-index:900; }
    .taxi-map-helper { display:flex; flex-wrap:wrap; gap:.38rem; margin-top:.6rem; }
    .taxi-map-helper button {
        padding:.48rem .66rem; border-radius:999px; border:1px solid rgba(17,17,17,.08); background:#fff; color:#4f584d; font-size:.6rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em;
    }
    .taxi-map-helper button.is-active { background:var(--green); border-color:var(--green); color:#fff; }
    .taxi-map-grid { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-top:.75rem; }
    .taxi-side-panel {
        background:#f7f8f5; border:1px solid rgba(17,17,17,.06); border-radius:6px; padding:.64rem;
        display:flex; flex-direction:column; gap:.72rem;
    }
    .taxi-side-panel__title { font-size:.56rem; color:#7a8375; letter-spacing:.12em; text-transform:uppercase; margin-bottom:.55rem; font-weight:800; }
    .taxi-trip-card { display:flex; flex-direction:column; gap:.68rem; }
    .taxi-trip-point { display:flex; align-items:flex-start; gap:.62rem; padding:.58rem .68rem; background:#fff; border-radius:14px; border:1px solid rgba(17,17,17,.06); }
    .taxi-trip-point__pin { width:10px; height:10px; border-radius:50%; flex-shrink:0; margin-top:3px; }
    .taxi-trip-point__pin.is-pickup { background:var(--green); box-shadow:0 0 8px rgba(0,149,67,.25); }
    .taxi-trip-point__pin.is-dropoff { background:var(--orange); box-shadow:0 0 8px rgba(22,163,74,.25); }
    .taxi-trip-point strong { display:block; font-size:.68rem; color:#7a8375; text-transform:uppercase; letter-spacing:.07em; margin-bottom:.15rem; }
    .taxi-trip-point span { color:#111; font-size:.8rem; line-height:1.45; }
    .taxi-estimate-panel { display:none; }
    .taxi-estimate-panel.is-visible { display:block; }
    .taxi-estimate-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:.48rem; }
    .taxi-estimate-stat { background:#fff; border:1px solid rgba(17,17,17,.06); border-radius:14px; padding:.58rem; }
    .taxi-estimate-stat small { display:block; font-size:.5rem; color:#7a8375; letter-spacing:.12em; text-transform:uppercase; margin-bottom:.18rem; }
    .taxi-estimate-stat strong { color:#111; font-size:.88rem; font-weight:900; }
    .taxi-price-band { margin-top:.52rem; display:flex; align-items:center; justify-content:space-between; gap:.65rem; background:#fff; border:1px solid rgba(17,17,17,.06); border-radius:14px; padding:.64rem .72rem; }
    .taxi-price-band small { display:block; color:#697465; line-height:1.42; font-size:.74rem; }
    .taxi-price-band strong { color:var(--green); font-size:1.05rem; font-weight:900; }
    .taxi-side-panel .taxi-formula { margin:0; gap:.38rem; }
    .taxi-side-panel .taxi-formula__btn {
        padding:.62rem 0; font-size:.68rem; letter-spacing:.04em;
    }
    .taxi-side-panel .taxi-formula__btn span { font-size:.56rem; margin-top:.15rem; }
    .taxi-side-panel .taxi-input-wrap { padding:.58rem .72rem; }
    .taxi-side-panel .taxi-field-label { margin-bottom:.3rem; }
    .taxi-side-panel > div[style] { margin-top:.72rem !important; }

    .taxi-zones { background:var(--ink); border-top:1px solid var(--line); }
    .taxi-zones__layout { display:grid; grid-template-columns:1fr 1fr; gap:3.25rem; align-items:center; margin-top:2.8rem; }
    .taxi-map-abstract {
        position:relative; height:360px; background:var(--paper); border:1px solid rgba(17,17,17,.08); border-radius:8px; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,.22);
    }
    .taxi-map-abstract__grid {
        position:absolute; inset:0; background-image:linear-gradient(rgba(0,149,67,.05) 1px,transparent 1px), linear-gradient(90deg,rgba(22,163,74,.04) 1px,transparent 1px); background-size:40px 40px;
    }
    .taxi-map-abstract__roads { position:absolute; inset:0; pointer-events:none; }
    .taxi-map-zone {
        position:absolute; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:900; text-transform:uppercase; letter-spacing:.06em; transition:transform .3s;
    }
    .taxi-map-zone:hover { transform:scale(1.05); }
    .taxi-map-zone.is-bzv { width:180px; height:180px; top:50%; left:55%; transform:translate(-50%,-50%); background:rgba(0,149,67,.15); border:1px solid rgba(0,149,67,.35); color:var(--green); }
    .taxi-map-zone.is-pnr { width:110px; height:110px; bottom:20%; left:18%; background:rgba(22,163,74,.1); border:1px solid rgba(22,163,74,.22); color:var(--orange); font-size:.62rem; }
    .taxi-map-zone.is-other { width:70px; height:70px; top:22%; right:14%; background:rgba(17,17,17,.05); border:1px solid rgba(17,17,17,.08); color:#7a8375; font-size:.55rem; }
    .taxi-map-pulse { position:absolute; top:50%; left:55%; transform:translate(-50%,-50%); width:20px; height:20px; border-radius:50%; background:var(--green); box-shadow:0 0 0 0 rgba(0,149,67,.45); animation:taxiMapPulse 2.5s ease-out infinite; z-index:2; }
    @keyframes taxiMapPulse { 0%{box-shadow:0 0 0 0 rgba(0,149,67,.45);} 70%{box-shadow:0 0 0 30px rgba(0,149,67,0);} 100%{box-shadow:0 0 0 0 rgba(0,149,67,0);} }
    .taxi-map-abstract__label { position:absolute; bottom:1.2rem; left:1.2rem; font-size:.55rem; color:#7a8375; letter-spacing:.1em; text-transform:uppercase; }
    .taxi-zones__list { display:flex; flex-direction:column; gap:1rem; }
    .taxi-zones__item {
        display:flex; align-items:flex-start; gap:1rem; padding:1.15rem 1.35rem; background:var(--paper); border:1px solid rgba(17,17,17,.08); border-radius:6px; border-left:3px solid transparent; transition:.22s;
        box-shadow:0 12px 28px rgba(0,0,0,.12);
    }
    .taxi-zones__item:hover { border-left-color:var(--orange); transform:translateX(4px); }
    .taxi-zones__item.is-active { border-left-color:var(--green); }
    .taxi-zones__dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; margin-top:4px; }
    .taxi-zones__dot.is-green { background:var(--green); box-shadow:0 0 8px rgba(0,149,67,.35); }
    .taxi-zones__dot.is-orange { background:var(--orange); box-shadow:0 0 8px rgba(22,163,74,.35); }
    .taxi-zones__dot.is-gray { background:#8a9280; }
    .taxi-zones__name { font-size:1rem; font-weight:900; text-transform:uppercase; color:#111; margin-bottom:.2rem; }
    .taxi-zones__desc { font-size:.74rem; color:#66705f; line-height:1.55; font-weight:300; }
    .taxi-zones__badge { margin-left:auto; flex-shrink:0; font-size:.55rem; letter-spacing:.1em; padding:3px 9px; border-radius:2px; text-transform:uppercase; font-weight:800; }
    .taxi-zones__badge.is-ok { background:rgba(0,149,67,.12); color:var(--green); border:1px solid rgba(0,149,67,.2); }
    .taxi-zones__badge.is-warn { background:rgba(22,163,74,.1); color:var(--orange); border:1px solid rgba(22,163,74,.2); }
    .taxi-zones__badge.is-soon { background:#f2f4f1; color:#7a8375; border:1px solid rgba(17,17,17,.08); }

    .taxi-cta { padding:5.5rem 0; background:var(--green); position:relative; overflow:hidden; }
    .taxi-cta::before { content:'TAXI'; position:absolute; right:-2rem; top:50%; transform:translateY(-50%); font-size:22vw; font-weight:900; color:rgba(255,255,255,.08); line-height:1; pointer-events:none; }
    .taxi-cta__inner { display:grid; grid-template-columns:1fr auto; gap:2.5rem; align-items:center; position:relative; z-index:1; }
    .taxi-cta__label { display:inline-flex; align-items:center; gap:10px; margin-bottom:1.2rem; font-size:.62rem; color:rgba(255,255,255,.65); letter-spacing:.22em; text-transform:uppercase; }
    .taxi-cta__label::before { content:''; width:24px; height:1px; background:#fff; }
    .taxi-cta__title { font-size:clamp(2.2rem,4.1vw,3.9rem); font-weight:900; text-transform:uppercase; letter-spacing:-.01em; line-height:.92; color:#fff; }
    .taxi-cta__title em { font-style:italic; opacity:.55; }
    .taxi-cta__text p { font-size:.84rem; color:rgba(255,255,255,.82); line-height:1.7; margin-top:1.1rem; max-width:430px; }
    .taxi-cta__buttons { display:flex; flex-direction:column; gap:.8rem; min-width:220px; }
    .taxi-cta__primary {
        background:#111; color:#fff; padding:1rem 1.8rem; border-radius:4px; font-size:.88rem; font-weight:900; letter-spacing:.09em; text-transform:uppercase; border:none;
        display:flex; align-items:center; justify-content:space-between; gap:1rem; box-shadow:0 4px 24px rgba(0,0,0,.25); text-decoration:none;
    }
    .taxi-cta__primary svg { width:16px; height:16px; }
    .taxi-cta__secondary {
        background:transparent; color:#fff; padding:1rem 1.8rem; border-radius:4px; font-size:.84rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase;
        border:1.5px solid rgba(255,255,255,.32); display:flex; align-items:center; justify-content:center; gap:.6rem; text-decoration:none;
    }
    .taxi-cta__secondary svg { width:14px; height:14px; }

    .taxi-footer { background:var(--ink-2); padding:4rem 3rem 2rem; border-top:1px solid var(--line); }
    .taxi-footer__grid { display:grid; grid-template-columns:2fr 1fr 1fr 1fr; gap:3rem; max-width:1200px; margin:0 auto; padding-bottom:3rem; border-bottom:1px solid var(--line); }
    .taxi-footer__brand-name { font-size:1.6rem; font-weight:900; color:#fff; margin-bottom:.8rem; display:flex; align-items:center; gap:.6rem; letter-spacing:-.04em; }
    .taxi-footer__brand-name span:first-child { color:var(--green); }
    .taxi-footer__brand-dot { width:8px; height:8px; background:var(--orange); border-radius:50%; margin-top:-10px; }
    .taxi-footer__desc { font-size:.78rem; color:var(--text-3); line-height:1.85; max-width:260px; margin-bottom:1.5rem; }
    .taxi-footer__socials { display:flex; gap:.6rem; }
    .taxi-footer__socials a { width:30px; height:30px; border-radius:50%; border:1px solid var(--line-2); display:flex; align-items:center; justify-content:center; color:var(--text-3); text-decoration:none; }
    .taxi-footer__col h4 { font-size:.6rem; font-weight:800; text-transform:uppercase; letter-spacing:.18em; color:var(--text-3); margin-bottom:1.2rem; }
    .taxi-footer__links { display:flex; flex-direction:column; gap:.55rem; }
    .taxi-footer__links a { font-size:.78rem; color:var(--text-3); text-decoration:none; transition:color .15s; }
    .taxi-footer__links a:hover { color:var(--text-2); }
    .taxi-footer__bottom { display:flex; justify-content:space-between; align-items:center; max-width:1200px; margin:0 auto; padding-top:2rem; flex-wrap:wrap; gap:.8rem; }
    .taxi-footer__copy { font-size:.62rem; color:var(--text-3); letter-spacing:.06em; }
    .taxi-footer__pay { display:flex; gap:.5rem; flex-wrap:wrap; }
    .taxi-footer__pay span { background:var(--ink-3); border:1px solid var(--line); border-radius:3px; padding:3px 9px; font-size:.55rem; font-weight:700; color:var(--text-3); letter-spacing:.06em; text-transform:uppercase; }
    .taxi-footer__legal { display:flex; gap:1.5rem; flex-wrap:wrap; }
    .taxi-footer__legal a { font-size:.6rem; color:var(--text-3); text-decoration:none; }

    .taxi-suggestions { display:none; position:relative; margin-top:.5rem; }
    .taxi-suggestions.is-visible { display:grid; gap:.35rem; }
    .taxi-suggestion-item {
        width:100%; text-align:left; padding:.7rem .9rem; background:#fff; border:1px solid rgba(17,17,17,.08); border-radius:4px; color:#111; font-size:.8rem;
    }
    .taxi-status { margin-top:.45rem; font-size:.72rem; color:#6f786d; line-height:1.55; }
    .taxi-note {
        width:100%; min-height:58px; margin-top:.45rem; border:1px solid rgba(17,17,17,.06); border-radius:14px; background:#f8f9f6; padding:.62rem .8rem; resize:vertical; outline:none; font-family:inherit; font-size:.74rem; color:#111;
    }
    .taxi-note::placeholder { color:#97a092; }
    .taxi-login-note, .taxi-legal, .taxi-hint { margin-top:.9rem; font-size:.72rem; line-height:1.6; color:#6f786d; }

    @media (max-width: 900px) {
        #taxiCursor { display:none; }
        body.bd-future-shell { cursor:auto; }
        .taxi-nav, .taxi-wrap, .taxi-footer { padding-left:1.25rem; padding-right:1.25rem; }
        .taxi-nav { padding:.8rem 1.5rem; }
        .taxi-nav__links { display:none; }
        .taxi-hero__inner, .taxi-howto__grid, .taxi-zones__layout, .taxi-cta__inner, .taxi-footer__grid { grid-template-columns:1fr; }
        .taxi-book-grid, .taxi-book-grid--tight, .taxi-options-grid { grid-template-columns:1fr; }
        .taxi-hero { min-height:auto; }
        .taxi-hero__inner { padding:7.1rem 1.1rem 0; gap:1.25rem; }
        .taxi-brand-mark { margin-bottom:1.1rem; }
        .taxi-book-panel { display:flex; max-width:100%; margin-top:.5rem; }
        .taxi-book-panel__body { padding:.78rem; gap:.65rem; }
        .taxi-formules__grid { grid-template-columns:1fr; }
        .taxi-hero-bottom { display:grid; grid-template-columns:1fr 1fr; }
        .taxi-hero-bottom__item { padding:1rem 1.1rem; }
        .taxi-book-search-cta { width:100%; min-height:52px; }
    }

    @media (min-width: 901px) {
        .taxi-stage-destination { display:block !important; }
    }

    @media (max-width: 767px) {
        .taxi-formula, .taxi-stats, .taxi-book-summary, .taxi-mode-row, .taxi-pay-row, .taxi-estimate-grid, .taxi-map-grid, .taxi-hero-bottom { grid-template-columns:1fr; display:grid; }
        .taxi-section { padding:4.25rem 0; }
        .taxi-hero__inner { padding:6.65rem .9rem 0; }
        .taxi-brand-mark__word { font-size:clamp(1.7rem, 10vw, 2.8rem); }
        .taxi-brand-mark__meta { gap:.45rem; margin-bottom:1.15rem; }
        .taxi-brand-mark__chip { font-size:.58rem; padding:.38rem .62rem; }
        .taxi-label { margin-bottom:1.15rem; }
        .taxi-title { font-size:clamp(2.6rem, 12vw, 4.2rem); }
        .taxi-sub { margin:1rem 0 1.2rem; max-width:none; }
        .taxi-book-panel { border-radius:16px; }
        .taxi-map-box, #taxiMap { min-height:220px; height:220px; }
        .taxi-map-abstract { height:300px; }
        .taxi-formules::before, .taxi-cta::before, .taxi-hero__num { display:none; }
        .taxi-cta__buttons { min-width:0; }
        .taxi-cta__primary, .taxi-cta__secondary { justify-content:center; }
        .taxi-footer { padding-left:1.25rem; padding-right:1.25rem; }
        .taxi-footer__bottom, .taxi-footer__legal, .taxi-footer__pay { justify-content:flex-start; }
        .taxi-wa { right:1rem; bottom:1rem; }
    }
</style>
@endsection

@section('content')
<div id="taxiCursor"><div id="taxiCursorRing"></div><div id="taxiCursorDot"></div></div>
<a class="taxi-wa" href="{{ $helpUrl }}" aria-label="Contacter le support BantuDelice">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
</a>

<div class="taxi-v1">
    <div class="taxi-ticker">
        <div class="taxi-ticker__track">
            <span class="taxi-ticker__item"><span class="taxi-ticker__sep"></span>Taxi urbain Brazzaville — Disponible maintenant</span>
            <span class="taxi-ticker__item"><span class="taxi-ticker__sep"></span>Paiement Mobile Money · Airtel · MTN · Especes</span>
            <span class="taxi-ticker__item"><span class="taxi-ticker__sep"></span>Formules Eco, Confort & XL — Prix estime avant depart</span>
            <span class="taxi-ticker__item"><span class="taxi-ticker__sep"></span>Suivi GPS temps reel — Depart immediat ou programme</span>
            <span class="taxi-ticker__item"><span class="taxi-ticker__sep"></span>Taxi urbain Brazzaville — Disponible maintenant</span>
            <span class="taxi-ticker__item"><span class="taxi-ticker__sep"></span>Paiement Mobile Money · Airtel · MTN · Especes</span>
            <span class="taxi-ticker__item"><span class="taxi-ticker__sep"></span>Formules Eco, Confort & XL — Prix estime avant depart</span>
            <span class="taxi-ticker__item"><span class="taxi-ticker__sep"></span>Suivi GPS temps reel — Depart immediat ou programme</span>
        </div>
    </div>

    <nav class="taxi-nav" id="taxiNav">
        <a class="taxi-nav__logo" href="{{ url('/') }}"><span class="taxi-nav__logo-word">BantuDelice</span><span class="taxi-nav__logo-dot"></span></a>
        <div class="taxi-nav__links">
            @if($foodEnabled)
                <a href="{{ $restaurantsUrl }}">Repas</a>
            @endif
            @if($colisEnabled)
                <a href="{{ $colisUrl }}">Colis</a>
            @endif
            <a class="is-active" href="{{ $taxiUrl }}">Taxi</a>
            <a href="{{ $carpoolUrl }}">Covoiturage</a>
            <a href="{{ $rentalUrl }}">Location</a>
            <a href="{{ $busUrl }}">Bus</a>
            <a href="#taxiZones">Zones</a>
        </div>
        <div class="taxi-nav__end">
            <a class="taxi-nav__lang" href="{{ $contactUrl }}">FR / EN</a>
            <a class="taxi-nav__cta" href="#taxiBooking">Reserver</a>
        </div>
    </nav>

    <section class="taxi-hero">
        <div class="taxi-hero__grid-bg"></div>
        <div class="taxi-hero__stripe"></div>
        <div class="taxi-hero__stripe-2"></div>
        <div class="taxi-hero__num">01</div>

        <div class="taxi-hero__inner">
            <div class="taxi-hero__content">
                <div class="taxi-brand-mark taxi-rv taxi-rv1">
                    <div class="taxi-brand-mark__word"><strong>Gessy</strong> Ride</div>
                    <span class="taxi-brand-mark__dot" aria-hidden="true"></span>
                </div>
                <div class="taxi-brand-mark__meta taxi-rv taxi-rv1">
                    <span class="taxi-brand-mark__chip">Mobilite urbaine</span>
                    <span class="taxi-brand-mark__chip">Brazzaville</span>
                    <span class="taxi-brand-mark__chip">Reserve en direct</span>
                </div>
                <div class="taxi-label taxi-rv taxi-rv1">Taxi urbain — Brazzaville</div>
                <h1 class="taxi-title taxi-rv taxi-rv2">
                    Votre<br>
                    <span class="accent">trajet,</span><br>
                    <span class="line-2">Maintenant.</span>
                </h1>
                <p class="taxi-sub taxi-rv taxi-rv3">Reservez en 30 secondes. Prix estime avant depart, chauffeur assigne rapidement, suivi GPS de bout en bout.</p>

                <div class="taxi-book-panel taxi-rv taxi-rv4" id="taxiBooking">
                    <div class="taxi-book-panel__header">
                        <div class="taxi-book-panel__title">Reserver un taxi</div>
                        <div class="taxi-book-panel__status"><span></span>Chauffeurs disponibles</div>
                    </div>
                    <div class="taxi-book-panel__body">
                        <div class="taxi-book-grid">
                            <div class="taxi-book-col taxi-book-col--pickup">
                                <label for="pickupInput" class="taxi-field-label">Point de depart</label>
                                <div class="taxi-input-wrap">
                                    <span class="taxi-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 13 8 13s8-7.75 8-13a8 8 0 0 0-8-8z"/></svg></span>
                                    <input type="text" id="pickupInput" placeholder="Adresse ou quartier..." autocomplete="off">
                                    <button type="button" id="locateMeBtn" class="taxi-locate-btn">GPS ↗</button>
                                </div>
                                <div id="pickupSuggestions" class="taxi-suggestions"></div>
                                <div class="taxi-progressive-section" data-progressive-section>
                                    <div id="pickupStatus" class="taxi-status">Nous pouvons recuperer votre position ou vous laisser saisir l adresse manuellement.</div>
                                    <textarea id="pickupNote" class="taxi-note" placeholder="Repere depart: portail, immeuble, station, commerce..."></textarea>
                                </div>
                            </div>
                            <div class="taxi-book-col taxi-stage-destination" data-destination-stage>
                                <label for="dropoffInput" class="taxi-field-label">Destination</label>
                                <div class="taxi-input-wrap">
                                    <span class="taxi-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg></span>
                                    <input type="text" id="dropoffInput" placeholder="Ou allez-vous ?" autocomplete="off">
                                </div>
                                <div id="dropoffSuggestions" class="taxi-suggestions"></div>
                                <div class="taxi-progressive-section" data-progressive-section>
                                    <div id="dropoffStatus" class="taxi-status">Choisissez votre arrivee par recherche ou en cliquant sur la carte.</div>
                                    <textarea id="dropoffNote" class="taxi-note" placeholder="Repere arrivee: quartier, batiment, barriere, pharmacie..."></textarea>
                                </div>
                            </div>
                            <button type="button" id="heroSearchBtn" class="taxi-book-search-cta">Rechercher</button>
                        </div>

                        <div class="taxi-progressive-grid" data-progressive-grid>
                            <div class="taxi-book-summary taxi-progressive-section" data-progressive-section>
                                <div class="taxi-book-summary__item"><div class="taxi-book-summary__key">Distance est.</div><div class="taxi-book-summary__val" id="estDistance">— km</div></div>
                                <div class="taxi-book-summary__item"><div class="taxi-book-summary__key">Duree est.</div><div class="taxi-book-summary__val" id="estDuration">— min</div></div>
                                <div class="taxi-book-summary__item"><div class="taxi-book-summary__key">Formule</div><div class="taxi-book-summary__val" id="heroSelectedFormula">Eco</div></div>
                                <div class="taxi-book-summary__item"><div class="taxi-book-summary__key">Tarif estime</div><div class="taxi-book-summary__val is-green" id="estPrice">{{ number_format((float) ($pricingData['minimum_fare'] ?? 0), 0, ',', ' ') }} FCFA</div></div>
                            </div>

                            <div class="taxi-book-grid--tight taxi-progressive-section" data-progressive-section>
                                <div>
                                    <label class="taxi-field-label">Moment du depart</label>
                                    <div class="taxi-mode-row">
                                        <label class="taxi-mode-card is-selected"><input type="radio" name="ride_timing" value="now" checked>Maintenant</label>
                                        <label class="taxi-mode-card"><input type="radio" name="ride_timing" value="later">Programmer</label>
                                    </div>
                                </div>

                                <div>
                                    <label class="taxi-field-label">Paiement</label>
                                    <div class="taxi-pay-row">
                                        <label class="taxi-mode-card is-selected"><input type="radio" name="payment_method" value="cash" checked>Especes</label>
                                        <label class="taxi-mode-card"><input type="radio" name="payment_method" value="momo">Mobile Money</label>
                                    </div>
                                </div>
                            </div>

                            <button id="confirmBtn" class="taxi-confirm taxi-progressive-section" data-progressive-section disabled>
                                Confirmer la course
                                <span class="taxi-confirm__price" id="heroConfirmPrice">{{ number_format((float) ($pricingData['minimum_fare'] ?? 0), 0, ',', ' ') }} F</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="taxi-hero-search-meta taxi-rv taxi-rv4">
                    <a href="#rideTrackerSection" class="taxi-hero-search-link">Suivi en direct</a>
                    <span class="taxi-hero-search-note">Saisie rapide, estimation immediate, confirmation ensuite.</span>
                </div>

                <div class="taxi-formula taxi-rv taxi-rv4" id="heroFormula">
                    @foreach($rideOptions as $index => $option)
                        <button type="button" class="taxi-formula__btn{{ $index === 0 ? ' is-active' : '' }}" data-hero-formula="{{ $option['key'] }}">
                            {{ $option['name'] }}
                            <span>{{ $option['base_label'] }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="taxi-stats taxi-rv taxi-rv5">
                    <div><span class="taxi-stats__num">3'</span><div class="taxi-stats__lbl">Temps moyen<br>d'attente</div></div>
                    <div><span class="taxi-stats__num">24/7</span><div class="taxi-stats__lbl">Disponibilite<br>service</div></div>
                    <div><span class="taxi-stats__num">100%</span><div class="taxi-stats__lbl">Prix visible<br>avant depart</div></div>
                </div>
            </div>
        </div>

        <div class="taxi-hero-bottom">
            <div class="taxi-hero-bottom__item">
                <div class="taxi-hero-bottom__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 13 8 13s8-7.75 8-13a8 8 0 0 0-8-8z"/></svg></div>
                <div><div class="taxi-hero-bottom__label">GPS temps reel</div><div style="font-size:.72rem;color:var(--text-3);">Position mise a jour en direct</div></div>
            </div>
            <div class="taxi-hero-bottom__item">
                <div class="taxi-hero-bottom__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
                <div><div class="taxi-hero-bottom__label">Prix transparent</div><div style="font-size:.72rem;color:var(--text-3);">Estimation avant confirmation</div></div>
            </div>
            <div class="taxi-hero-bottom__item">
                <div class="taxi-hero-bottom__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.48 2 2 0 0 1 3.58 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6.13 6.13l1.92-1.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
                <div><div class="taxi-hero-bottom__label">Support WhatsApp</div><div style="font-size:.72rem;color:var(--text-3);">Assistance pendant votre trajet</div></div>
            </div>
            <div class="taxi-hero-bottom__item">
                <div class="taxi-hero-bottom__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                <div><div class="taxi-hero-bottom__label">Chauffeurs verifies</div><div style="font-size:.72rem;color:var(--text-3);">Identite et vehicule controles</div></div>
            </div>
        </div>
    </section>

    <section class="taxi-section taxi-formules">
        <div class="taxi-wrap">
            <div class="taxi-section-label">Nos formules</div>
            <h2 class="taxi-section-title">Choisissez<br>votre <em>confort.</em></h2>
            <div class="taxi-formules__grid">
                <article class="taxi-formule-card">
                    <div class="taxi-formule-card__num">01</div>
                    <div class="taxi-formule-card__tag">Standard</div>
                    <div class="taxi-formule-card__name">Eco</div>
                    <div class="taxi-formule-card__desc">Le trajet du quotidien. Rapide, accessible, efficace. Ideal pour les deplacements courts en ville.</div>
                    <div class="taxi-formule-card__price">{{ number_format(max(round(($pricingData['minimum_fare'] ?? 2500)), 2500), 0, ',', ' ') }} F</div>
                    <div class="taxi-formule-card__sub">Tarif de base · + {{ number_format(max(round(($pricingData['price_per_km'] ?? 220)), 220), 0, ',', ' ') }} F/km</div>
                    <ul class="taxi-formule-card__features">
                        <li><span class="taxi-formule-card__check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>1 a 3 passagers</li>
                        <li><span class="taxi-formule-card__check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Bagage cabine inclus</li>
                        <li><span class="taxi-formule-card__check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Suivi GPS en direct</li>
                    </ul>
                    <div class="taxi-formule-card__cta">Choisir Eco <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
                </article>

                <article class="taxi-formule-card is-featured">
                    <div class="taxi-formule-card__num">02</div>
                    <div class="taxi-formule-card__tag">Populaire</div>
                    <div class="taxi-formule-card__name">Confort</div>
                    <div class="taxi-formule-card__desc">Plus d'espace, prise en charge plus douce, chauffeur selectionne. Le bon choix pour la majorite des trajets.</div>
                    <div class="taxi-formule-card__price">{{ number_format(max(round(($pricingData['minimum_fare'] ?? 2500) * 1.18), 4000), 0, ',', ' ') }} F</div>
                    <div class="taxi-formule-card__sub">Tarif de base · coefficient 1,18</div>
                    <ul class="taxi-formule-card__features">
                        <li><span class="taxi-formule-card__check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>1 a 4 passagers</li>
                        <li><span class="taxi-formule-card__check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Climatisation garantie</li>
                        <li><span class="taxi-formule-card__check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Suivi GPS + partage de trajet</li>
                    </ul>
                    <div class="taxi-formule-card__cta">Choisir Confort <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
                </article>

                <article class="taxi-formule-card">
                    <div class="taxi-formule-card__num">03</div>
                    <div class="taxi-formule-card__tag">Grand groupe</div>
                    <div class="taxi-formule-card__name">XL</div>
                    <div class="taxi-formule-card__desc">Ideal si vous etes plusieurs ou avec beaucoup de bagages. Vehicule spacieux, prise en charge adaptee.</div>
                    <div class="taxi-formule-card__price">{{ number_format(max(round(($pricingData['minimum_fare'] ?? 2500) * 1.35), 5500), 0, ',', ' ') }} F</div>
                    <div class="taxi-formule-card__sub">Tarif de base · coefficient 1,35</div>
                    <ul class="taxi-formule-card__features">
                        <li><span class="taxi-formule-card__check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Jusqu'a 6 passagers</li>
                        <li><span class="taxi-formule-card__check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Grand coffre — bagages multiples</li>
                        <li><span class="taxi-formule-card__check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>Ideal aeroport / demenagement</li>
                    </ul>
                    <div class="taxi-formule-card__cta">Choisir XL <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
                </article>
            </div>
        </div>
    </section>

    <section class="taxi-section taxi-howto">
        <div class="taxi-wrap">
            <div class="taxi-section-label">Fonctionnement</div>
            <h2 class="taxi-section-title">Simple.<br><em>Tres simple.</em></h2>
            <div class="taxi-howto__grid">
                <div class="taxi-howto__steps">
                    <div class="taxi-howto__step"><div class="taxi-howto__num">01</div><div><div class="taxi-howto__title">Saisissez votre trajet</div><div class="taxi-howto__desc">Entrez votre point de depart et votre destination. Utilisez la geolocalisation automatique ou tapez l'adresse manuellement.</div></div></div>
                    <div class="taxi-howto__step"><div class="taxi-howto__num">02</div><div><div class="taxi-howto__title">Choisissez votre formule</div><div class="taxi-howto__desc">Eco, Confort ou XL — le prix s'affiche avant que vous confirmiez. Aucune surprise a l'arrivee.</div></div></div>
                    <div class="taxi-howto__step"><div class="taxi-howto__num">03</div><div><div class="taxi-howto__title">Confirmez et attendez</div><div class="taxi-howto__desc">Un chauffeur vous est assigne immediatement. Suivez sa position en temps reel jusqu'a sa prise en charge.</div></div></div>
                    <div class="taxi-howto__step"><div class="taxi-howto__num">04</div><div><div class="taxi-howto__title">Payez a l'arrivee</div><div class="taxi-howto__desc">Especes a bord ou Mobile Money. Le recu et les details de course restent exploitables ensuite.</div></div></div>
                </div>

                <div class="taxi-howto__visual">
                    <div class="taxi-map-head">
                        <div class="taxi-map-head__title">Course en cours</div>
                        <span class="taxi-geostate" id="geoState" data-state="idle"><i class="fas fa-crosshairs"></i> Attente GPS</span>
                    </div>
                    <div class="taxi-map-placeholder" data-map-placeholder>La carte s'affichera apres saisie du depart et de la destination.</div>
                    <div class="taxi-map-box taxi-progressive-section" data-progressive-section><div id="taxiMap"></div></div>
                    <div class="taxi-map-helper taxi-progressive-section" data-progressive-section>
                        <button type="button" id="setPickupPinBtn" class="is-active">Repere depart</button>
                        <button type="button" id="setDropoffPinBtn">Repere arrivee</button>
                        <button type="button" id="centerRouteBtn">Centrer la carte</button>
                        <button type="button" id="clearRouteBtn">Reinitialiser</button>
                    </div>

                    <div class="taxi-progressive-grid" data-progressive-grid>
                        <div class="taxi-map-grid taxi-progressive-section" data-progressive-section>
                            <div class="taxi-side-panel">
                                <div class="taxi-side-panel__title">Resume</div>
                                <div class="taxi-trip-card">
                                    <article class="taxi-trip-point">
                                        <span class="taxi-trip-point__pin is-pickup"></span>
                                        <div><strong>Depart</strong><span id="summaryPickup">Non defini pour l instant</span></div>
                                    </article>
                                    <article class="taxi-trip-point">
                                        <span class="taxi-trip-point__pin is-dropoff"></span>
                                        <div><strong>Arrivee</strong><span id="summaryDropoff">Ajoutez une destination</span></div>
                                    </article>
                                </div>
                            </div>

                            <div class="taxi-side-panel">
                                <div class="taxi-side-panel__title">Estimation</div>
                                <div id="estimateSection" class="taxi-estimate-panel">
                                    <div class="taxi-estimate-grid">
                                        <article class="taxi-estimate-stat"><small>Distance</small><strong id="estDistanceMap">-- km</strong></article>
                                        <article class="taxi-estimate-stat"><small>Duree</small><strong id="estDurationMap">-- min</strong></article>
                                        <article class="taxi-estimate-stat"><small>Tarif min.</small><strong id="estBaseFare">{{ number_format((float) ($pricingData['minimum_fare'] ?? 0), 0, ',', ' ') }} FCFA</strong></article>
                                    </div>
                                    <div class="taxi-price-band">
                                        <div><small id="selectedRideMeta">Selectionnez une formule pour affiner la course.</small></div>
                                        <strong id="estPriceMap">-- FCFA</strong>
                                    </div>
                                </div>
                                <div class="taxi-hint">La carte sert de verification visuelle. La reservation finale partira avec vos coordonnees GPS, vos reperes et votre formule.</div>
                            </div>
                        </div>

                        <div class="taxi-side-panel taxi-progressive-section" data-progressive-section style="margin-top:0;">
                            <div class="taxi-side-panel__title">Options de course</div>
                            <div class="taxi-options-grid">
                                <div>
                                    <div class="taxi-formula" id="rideOptionGrid" style="margin:0;">
                                        @foreach($rideOptions as $index => $option)
                                            <button
                                                type="button"
                                                class="taxi-formula__btn{{ $index === 0 ? ' is-active' : '' }}"
                                                data-ride-option
                                                data-option-key="{{ $option['key'] }}"
                                                data-option-name="{{ $option['name'] }}"
                                                data-option-multiplier="{{ $option['multiplier'] }}"
                                            >
                                                {{ $option['name'] }}
                                                <span id="ridePrice{{ ucfirst($option['key']) }}">--</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="taxi-options-stack">
                                    <div style="margin-top:0;">
                                        <label for="scheduledAtInput" class="taxi-field-label">Depart programme</label>
                                        <div class="taxi-input-wrap">
                                            <input type="datetime-local" id="scheduledAtInput" disabled>
                                        </div>
                                    </div>
                                    <div style="margin-top:0;">
                                        <label for="passengerCount" class="taxi-field-label">Nombre de passagers</label>
                                        <div class="taxi-input-wrap">
                                            <select id="passengerCount">
                                                <option value="1">1 passager</option>
                                                <option value="2">2 passagers</option>
                                                <option value="3">3 passagers</option>
                                                <option value="4">4 passagers</option>
                                                <option value="5">5 passagers</option>
                                                <option value="6">6 passagers ou plus</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="p_lat">
                    <input type="hidden" id="p_lng">
                    <input type="hidden" id="d_lat">
                    <input type="hidden" id="d_lng">
                    <input type="hidden" id="estimatedDistance">
                    <input type="hidden" id="estimatedDuration">
                    <input type="hidden" id="estimatedPriceValue">
                    <input type="hidden" id="selectedRideOption" value="eco">
                </div>
            </div>
        </div>
    </section>

    <section class="taxi-section taxi-zones" id="taxiZones">
        <div class="taxi-wrap">
            <div class="taxi-section-label">Couverture</div>
            <h2 class="taxi-section-title">Ou nous<br><em>operons.</em></h2>
            <div class="taxi-zones__layout">
                <div class="taxi-map-abstract">
                    <div class="taxi-map-abstract__grid"></div>
                    <svg class="taxi-map-abstract__roads" viewBox="0 0 420 440">
                        <path d="M210 80 Q280 150 300 220 Q320 290 280 360" stroke="rgba(22,163,74,.08)" stroke-width="2" fill="none"/>
                        <path d="M80 180 Q160 200 210 220 Q280 240 340 260" stroke="rgba(0,149,67,.06)" stroke-width="1.5" fill="none"/>
                        <path d="M140 80 Q160 160 155 220 Q150 280 130 360" stroke="rgba(17,17,17,.08)" stroke-width="1" fill="none"/>
                        <path d="M80 300 Q130 310 155 320 Q180 330 190 360" stroke="rgba(0,149,67,.08)" stroke-width="1.5" fill="none"/>
                    </svg>
                    <div class="taxi-map-zone is-bzv">Brazzaville</div>
                    <div class="taxi-map-pulse"></div>
                    <div class="taxi-map-zone is-pnr">Pte-Noire</div>
                    <div class="taxi-map-zone is-other">Dolisie</div>
                    <div class="taxi-map-abstract__label">Reseau · en expansion</div>
                </div>

                <div class="taxi-zones__list">
                    <div class="taxi-zones__item is-active">
                        <div class="taxi-zones__dot is-green"></div>
                        <div><div class="taxi-zones__name">Brazzaville</div><div class="taxi-zones__desc">Couverture complete des arrondissements principaux. Service disponible tous les jours avec attribution rapide.</div></div>
                        <div class="taxi-zones__badge is-ok">Actif</div>
                    </div>
                    <div class="taxi-zones__item">
                        <div class="taxi-zones__dot is-orange"></div>
                        <div><div class="taxi-zones__name">Pointe-Noire</div><div class="taxi-zones__desc">Service en deploiement progressif. Disponible sur les zones principales. Confirmer la disponibilite avant reservation.</div></div>
                        <div class="taxi-zones__badge is-warn">Partiel</div>
                    </div>
                    <div class="taxi-zones__item">
                        <div class="taxi-zones__dot is-gray"></div>
                        <div><div class="taxi-zones__name">Autres villes</div><div class="taxi-zones__desc">Dolisie, Nkayi et autres villes en cours d'integration. Extension progressive du reseau.</div></div>
                        <div class="taxi-zones__badge is-soon">Bientot</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="taxi-cta">
        <div class="taxi-wrap">
            <div class="taxi-cta__inner">
                <div class="taxi-cta__text">
                    <div class="taxi-cta__label">Pret a partir</div>
                    <h2 class="taxi-cta__title">Votre chauffeur<br><em>vous attend.</em></h2>
                    <p>Reservez votre taxi en moins de 30 secondes. Prix transparent, chauffeur verifie, trajet suivi de bout en bout.</p>
                </div>
                <div class="taxi-cta__buttons">
                    <a class="taxi-cta__primary" href="#taxiBooking">Reserver maintenant <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                    <a class="taxi-cta__secondary" href="{{ $contactUrl }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.48 2 2 0 0 1 3.58 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6.13 6.13l1.92-1.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>Contacter le support</a>
                </div>
            </div>
        </div>
    </section>

    <footer class="taxi-footer">
        <div class="taxi-footer__grid">
            <div>
                <div class="taxi-footer__brand-name"><span>BantuDelice</span><span class="taxi-footer__brand-dot"></span></div>
                <p class="taxi-footer__desc">Votre partenaire pour la livraison de repas, l'expedition de colis et le transport urbain. Concu pour le Congo.</p>
                <div class="taxi-footer__socials">
                    <a href="https://www.facebook.com/BantuDelice" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/bantudelice.cg/" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="taxi-footer__col">
                <h4>Services</h4>
                <div class="taxi-footer__links">
                    <a href="{{ $taxiUrl }}">Taxi urbain</a>
                    @if($colisEnabled)
                        <a href="{{ $colisUrl }}">Livraison colis</a>
                    @endif
                    @if($foodEnabled)
                        <a href="{{ $restaurantsUrl }}">Livraison repas</a>
                    @endif
                    <a href="{{ $driverUrl }}">Devenir chauffeur</a>
                    <a href="{{ $partnerUrl }}">Devenir partenaire</a>
                </div>
            </div>
            <div class="taxi-footer__col">
                <h4>Informations</h4>
                <div class="taxi-footer__links">
                    <a href="{{ route('terms.conditions') }}">Conditions generales</a>
                    <a href="{{ $privacyUrl }}">Confidentialite</a>
                    <a href="{{ $faqUrl }}">FAQ</a>
                    <a href="{{ $helpUrl }}">Centre d'aide</a>
                    <a href="{{ $contactUrl }}">Nous contacter</a>
                </div>
            </div>
            <div class="taxi-footer__col">
                <h4>Ressources</h4>
                <div class="taxi-footer__links">
                    @if($foodEnabled)
                        <a href="{{ $trackOrderUrl }}">Suivre une commande</a>
                    @endif
                    @if($colisEnabled)
                        <a href="{{ $colisUrl }}">Suivre un colis</a>
                    @endif
                    <a href="{{ $offersUrl }}">Voir les offres</a>
                    <a href="{{ route('site.map') }}">Plan du site</a>
                </div>
            </div>
        </div>
        <div class="taxi-footer__bottom">
            <div class="taxi-footer__copy">© 2026 BantuDelice. Tous droits reserves. Republique du Congo.</div>
            <div class="taxi-footer__pay"><span>Mobile Money</span><span>Airtel Money</span><span>MTN MoMo</span><span>Cash</span></div>
            <div class="taxi-footer__legal"><a href="{{ $legalUrl }}">Mentions legales</a><a href="{{ $cookiesUrl }}">Cookies</a></div>
        </div>
    </footer>
</div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    let map, pickupMarker, dropoffMarker, routeLayer;
    let pickupSearchTimeout = null, dropoffSearchTimeout = null;
    let lastPickupQuery = '', lastDropoffQuery = '';
    let activePinTarget = 'pickup';
    let currentEstimate = { distance: 0, duration: 0, basePrice: 0, finalPrice: 0 };

    const MAPBOX_TOKEN = @json(mapbox_public_token());
    const DEFAULT_CITY = { lat: -4.2767, lng: 15.2832, label: 'Brazzaville, Republique du Congo' };
    const RIDE_OPTIONS = @json($rideOptions);
    const PRICING = @json($pricingData);
    const TRANSPORT_AUTHENTICATED = @json(auth()->check());
    const LOGIN_URL = @json(route('user.login', ['redirect' => url()->current()]));

    document.addEventListener('DOMContentLoaded', () => {
        const dot = document.getElementById('taxiCursorDot');
        const ring = document.getElementById('taxiCursorRing');
        let mx = 0, my = 0, rx = 0, ry = 0;

        if (dot && ring && window.matchMedia('(min-width: 901px)').matches) {
            document.addEventListener('mousemove', (e) => {
                mx = e.clientX; my = e.clientY;
                dot.style.transform = `translate(${mx}px,${my}px) translate(-50%,-50%)`;
            });
            (function animCursor() {
                rx += (mx - rx) * 0.16;
                ry += (my - ry) * 0.16;
                ring.style.transform = `translate(${rx}px,${ry}px) translate(-50%,-50%)`;
                requestAnimationFrame(animCursor);
            }());
        }

        const nav = document.getElementById('taxiNav');
        window.addEventListener('scroll', () => {
            if (!nav) return;
            nav.classList.toggle('is-compact', scrollY > 60);
        });

        bindHeroFormula();
        initTaxiMap();
    });

    function bindHeroFormula() {
        document.querySelectorAll('[data-hero-formula]').forEach((button) => {
            button.addEventListener('click', () => {
                const key = button.dataset.heroFormula;
                document.querySelectorAll('[data-hero-formula]').forEach((item) => item.classList.remove('is-active'));
                button.classList.add('is-active');
                const mapped = key === 'comfort' ? 'comfort' : key;
                const matchingRideButton = document.querySelector(`[data-ride-option][data-option-key="${mapped}"]`);
                if (matchingRideButton) matchingRideButton.click();
            });
        });
    }

    function initTaxiMap() {
        const mapBox = document.getElementById('taxiMap');
        if (!mapBox) return;

        bindRideOptions();
        bindTimingControls();
        bindPaymentCards();
        bindSummaryPrefill();
        bindHeroSearchButton();

        if (!MAPBOX_TOKEN) {
            mapBox.innerHTML = '<div style="padding:2rem;color:#64748b;">Mapbox non configure. Ajoutez MAPBOX_PUBLIC_TOKEN pour activer la carte taxi.</div>';
            updateGeoState('error', 'Mapbox absent');
            return;
        }

        map = L.map('taxiMap', { zoomControl: true }).setView([DEFAULT_CITY.lat, DEFAULT_CITY.lng], 13);

        L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/{z}/{x}/{y}?access_token=' + MAPBOX_TOKEN, {
            tileSize: 512,
            zoomOffset: -1,
            attribution: '&copy; OpenStreetMap contributors &copy; Mapbox',
            maxZoom: 19
        }).addTo(map);

        wireAddressSearch('pickup');
        wireAddressSearch('dropoff');
        wirePinHelpers();
        detectGeoPermission();
        requestCurrentPosition();

        const locateBtn = document.getElementById('locateMeBtn');
        if (locateBtn) locateBtn.addEventListener('click', () => requestCurrentPosition(true));

        map.on('click', async (event) => {
            const target = activePinTarget || 'pickup';
            const details = await reverseGeocode({ lat: event.latlng.lat, lng: event.latlng.lng });
            applySelectedAddress(target, details);
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('.taxi-book-panel') && !event.target.closest('.taxi-howto__visual')) {
                hideSuggestions('pickup');
                hideSuggestions('dropoff');
            }
        });
    }

    function bindRideOptions() {
        document.querySelectorAll('[data-ride-option]').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('[data-ride-option]').forEach((item) => item.classList.remove('is-active'));
                document.querySelectorAll('[data-hero-formula]').forEach((item) => item.classList.toggle('is-active', item.dataset.heroFormula === button.dataset.optionKey));
                button.classList.add('is-active');
                document.getElementById('selectedRideOption').value = button.dataset.optionKey;
                const heroFormula = document.getElementById('heroSelectedFormula');
                if (heroFormula) heroFormula.textContent = button.dataset.optionName || 'Eco';
                refreshEstimatePrice();
            });
        });
    }

    function bindTimingControls() {
        const timingInputs = document.querySelectorAll('input[name="ride_timing"]');
        const scheduledField = document.getElementById('scheduledAtInput');
        timingInputs.forEach((input) => {
            input.addEventListener('change', () => {
                const isLater = document.querySelector('input[name="ride_timing"]:checked')?.value === 'later';
                if (scheduledField) {
                    scheduledField.disabled = !isLater;
                    if (!isLater) scheduledField.value = '';
                }
                document.querySelectorAll('.taxi-mode-row .taxi-mode-card').forEach((card) => {
                    const radio = card.querySelector('input');
                    card.classList.toggle('is-selected', radio?.checked);
                });
            });
        });
    }

    function bindPaymentCards() {
        document.querySelectorAll('.taxi-pay-row .taxi-mode-card input[name="payment_method"]').forEach((input) => {
            input.addEventListener('change', () => {
                document.querySelectorAll('.taxi-pay-row .taxi-mode-card').forEach((card) => {
                    const radio = card.querySelector('input');
                    card.classList.toggle('is-selected', radio?.checked);
                });
            });
        });
    }

    function bindSummaryPrefill() {
        const pickupInput = document.getElementById('pickupInput');
        const dropoffInput = document.getElementById('dropoffInput');

        if (pickupInput) {
            pickupInput.addEventListener('input', () => {
                document.getElementById('summaryPickup').textContent = pickupInput.value.trim() || 'Non defini pour l instant';
                updateProgressiveFormState();
            });
        }

        if (dropoffInput) {
            dropoffInput.addEventListener('input', () => {
                document.getElementById('summaryDropoff').textContent = dropoffInput.value.trim() || 'Ajoutez une destination';
                updateProgressiveFormState();
            });
        }

        updateProgressiveFormState();
    }

    function bindHeroSearchButton() {
        const heroSearchBtn = document.getElementById('heroSearchBtn');
        if (!heroSearchBtn) return;

        heroSearchBtn.addEventListener('click', async () => {
            const pickupInput = document.getElementById('pickupInput');
            const dropoffInput = document.getElementById('dropoffInput');
            const pickupValue = pickupInput?.value.trim() || '';
            const dropoffValue = dropoffInput?.value.trim() || '';

            if (!pickupValue) {
                pickupInput?.focus();
                return;
            }

            if (!dropoffValue) {
                dropoffInput?.focus();
                updateProgressiveFormState();
                return;
            }

            if (!pickupMarker && pickupValue.length >= 3) {
                const pickupResult = await geocodeAddress(pickupValue);
                if (pickupResult) applySelectedAddress('pickup', pickupResult);
            }

            if (!dropoffMarker && dropoffValue.length >= 3) {
                const dropoffResult = await geocodeAddress(dropoffValue);
                if (dropoffResult) applySelectedAddress('dropoff', dropoffResult);
            }

            updateProgressiveFormState();

            if (pickupMarker && dropoffMarker) {
                await calculateRoute();
            }
        });
    }

    function detectGeoPermission() {
        if (!navigator.permissions || !navigator.permissions.query) {
            updateGeoState('idle', 'GPS navigateur');
            return;
        }

        navigator.permissions.query({ name: 'geolocation' }).then((result) => {
            if (result.state === 'granted') updateGeoState('success', 'GPS autorise');
            else if (result.state === 'denied') updateGeoState('error', 'GPS bloque');
            else updateGeoState('idle', 'GPS a autoriser');
            result.onchange = () => detectGeoPermission();
        }).catch(() => updateGeoState('idle', 'GPS navigateur'));
    }

    function updateGeoState(state, label) {
        const chip = document.getElementById('geoState');
        if (!chip) return;
        chip.dataset.state = state;
        chip.innerHTML = `<i class="fas fa-crosshairs"></i> ${label}`;
    }

    function wireAddressSearch(type) {
        const input = document.getElementById(type === 'pickup' ? 'pickupInput' : 'dropoffInput');
        if (!input) return;

        input.addEventListener('focus', () => setActivePinTarget(type));

        input.addEventListener('input', () => {
            const query = input.value.trim();
            if (type === 'pickup') {
                lastPickupQuery = query;
                if (pickupSearchTimeout) clearTimeout(pickupSearchTimeout);
                if (query.length < 3) { hideSuggestions(type); return; }
                pickupSearchTimeout = setTimeout(() => searchAddressSuggestions(type, query), 250);
                return;
            }

            lastDropoffQuery = query;
            if (dropoffSearchTimeout) clearTimeout(dropoffSearchTimeout);
            if (query.length < 3) { hideSuggestions(type); return; }
            dropoffSearchTimeout = setTimeout(() => searchAddressSuggestions(type, query), 250);
        });

        input.addEventListener('keydown', async (event) => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const query = input.value.trim();
            if (query.length < 3) return;
            const result = await geocodeAddress(query);
            if (result) applySelectedAddress(type, result);
        });

        input.addEventListener('focus', () => {
            const suggestions = document.getElementById(type + 'Suggestions');
            if (suggestions && suggestions.children.length) suggestions.classList.add('is-visible');
        });
    }

    function wirePinHelpers() {
        const pickupBtn = document.getElementById('setPickupPinBtn');
        const dropoffBtn = document.getElementById('setDropoffPinBtn');
        const clearRouteBtn = document.getElementById('clearRouteBtn');
        const centerRouteBtn = document.getElementById('centerRouteBtn');

        if (pickupBtn) pickupBtn.addEventListener('click', () => setActivePinTarget('pickup'));
        if (dropoffBtn) dropoffBtn.addEventListener('click', () => setActivePinTarget('dropoff'));

        if (centerRouteBtn) {
            centerRouteBtn.addEventListener('click', () => {
                if (!map) return;
                if (routeLayer) { map.fitBounds(routeLayer.getBounds(), { padding: [30, 30] }); return; }
                if (pickupMarker) { map.setView(pickupMarker.getLatLng(), 15); return; }
                map.setView([DEFAULT_CITY.lat, DEFAULT_CITY.lng], 13);
            });
        }

        if (clearRouteBtn) {
            clearRouteBtn.addEventListener('click', () => {
                if (pickupMarker) map.removeLayer(pickupMarker);
                if (dropoffMarker) map.removeLayer(dropoffMarker);
                if (routeLayer) map.removeLayer(routeLayer);
                pickupMarker = null; dropoffMarker = null; routeLayer = null;
                ['pickupInput', 'dropoffInput', 'pickupNote', 'dropoffNote', 'p_lat', 'p_lng', 'd_lat', 'd_lng', 'estimatedDistance', 'estimatedDuration', 'estimatedPriceValue'].forEach((id) => {
                    const field = document.getElementById(id);
                    if (field) field.value = '';
                });
                updatePickupStatus('Depart a repositionner.');
                updateDropoffStatus('Arrivee a repositionner.');
                document.getElementById('estimateSection').classList.remove('is-visible');
                document.getElementById('confirmBtn').disabled = true;
                document.getElementById('summaryPickup').textContent = 'Non defini pour l instant';
                document.getElementById('summaryDropoff').textContent = 'Ajoutez une destination';
                document.getElementById('estDistance').textContent = '-- km';
                document.getElementById('estDuration').textContent = '-- min';
                document.getElementById('heroSelectedFormula').textContent = 'Eco';
                document.getElementById('estPrice').textContent = '-- FCFA';
                document.getElementById('estDistanceMap').textContent = '-- km';
                document.getElementById('estDurationMap').textContent = '-- min';
                document.getElementById('estPriceMap').textContent = '-- FCFA';
                document.getElementById('heroConfirmPrice').textContent = '{{ number_format((float) ($pricingData['minimum_fare'] ?? 0), 0, ',', ' ') }} F';
                map.setView([DEFAULT_CITY.lat, DEFAULT_CITY.lng], 13);
                setActivePinTarget('pickup');
                updateProgressiveFormState();
            });
        }
    }

    function setActivePinTarget(type) {
        activePinTarget = type;
        const pickupBtn = document.getElementById('setPickupPinBtn');
        const dropoffBtn = document.getElementById('setDropoffPinBtn');
        if (pickupBtn) pickupBtn.classList.toggle('is-active', type === 'pickup');
        if (dropoffBtn) dropoffBtn.classList.toggle('is-active', type === 'dropoff');
    }

    function hasRouteInputs() {
        const pickupValue = document.getElementById('pickupInput')?.value.trim() || '';
        const dropoffValue = document.getElementById('dropoffInput')?.value.trim() || '';
        return Boolean(pickupValue && dropoffValue);
    }

    function updateProgressiveFormState() {
        const pickupValue = document.getElementById('pickupInput')?.value.trim() || '';
        const isDestinationVisible = Boolean(pickupValue);
        const isVisible = hasRouteInputs();

        document.querySelectorAll('[data-destination-stage]').forEach((node) => {
            node.classList.toggle('is-visible', isDestinationVisible);
        });

        document.querySelectorAll('[data-map-placeholder]').forEach((node) => {
            node.classList.toggle('is-hidden', isVisible);
        });

        document.querySelectorAll('[data-progressive-section]').forEach((node) => {
            node.classList.toggle('is-visible', isVisible);
        });
        document.querySelectorAll('[data-progressive-grid]').forEach((node) => {
            node.classList.toggle('is-visible', isVisible);
        });
    }

    async function searchAddressSuggestions(type, query) {
        const currentQuery = type === 'pickup' ? lastPickupQuery : lastDropoffQuery;
        if (query !== currentQuery) return;
        const suggestions = await geocodeAddressList(query);
        renderSuggestions(type, suggestions);
    }

    async function geocodeAddress(query) {
        const items = await geocodeAddressList(query, 1);
        return items[0] || null;
    }

    async function geocodeAddressList(query, limit = 5) {
        try {
            const proximity = getPickupCoordinates();
            let url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json?access_token=${MAPBOX_TOKEN}&autocomplete=true&limit=${limit}&language=fr&country=cg&types=address,poi,neighborhood,locality,place`;
            if (proximity) url += `&proximity=${proximity.lng},${proximity.lat}`;
            const res = await fetch(url);
            const data = await res.json();
            if (!data.features) return [];
            return data.features.map((feature) => {
                const [lng, lat] = feature.center;
                return buildAddressDetails(lat, lng, feature);
            });
        } catch (e) {
            console.error('Geocoding Mapbox error:', e);
            return [];
        }
    }

    function renderSuggestions(type, suggestions) {
        const box = document.getElementById(type + 'Suggestions');
        if (!box) return;
        box.innerHTML = '';
        if (!suggestions.length) { box.classList.remove('is-visible'); return; }
        suggestions.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'taxi-suggestion-item';
            button.textContent = item.label;
            button.addEventListener('click', () => applySelectedAddress(type, item));
            box.appendChild(button);
        });
        box.classList.add('is-visible');
    }

    function hideSuggestions(type) {
        const box = document.getElementById(type + 'Suggestions');
        if (!box) return;
        box.classList.remove('is-visible');
    }

    function applySelectedAddress(type, item) {
        const input = document.getElementById(type === 'pickup' ? 'pickupInput' : 'dropoffInput');
        if (input) input.value = item.label || item.addressLine || '';

        const noteField = document.getElementById(type === 'pickup' ? 'pickupNote' : 'dropoffNote');
        if (noteField && !noteField.value && (item.landmark || item.district)) {
            noteField.value = [item.landmark, item.district].filter(Boolean).join(' | ');
        }

        setMarker(type, item);
        hideSuggestions(type);

        if (type === 'pickup') {
            updatePickupStatus(item.district ? `Depart confirme: ${item.district}` : 'Position de depart definie.');
            document.getElementById('summaryPickup').textContent = input?.value || item.label || 'Depart defini';
            map.setView([item.lat, item.lng], 15);
            updateProgressiveFormState();
            return;
        }

        updateDropoffStatus(item.district ? `Arrivee confirmee: ${item.district}` : 'Destination positionnee.');
        document.getElementById('summaryDropoff').textContent = input?.value || item.label || 'Destination definie';
        updateProgressiveFormState();
    }

    function updatePickupStatus(message, isError = false) {
        const status = document.getElementById('pickupStatus');
        if (!status) return;
        status.textContent = message;
        status.style.color = isError ? '#16a34a' : '#6f786d';
    }

    function updateDropoffStatus(message, isError = false) {
        const status = document.getElementById('dropoffStatus');
        if (!status) return;
        status.textContent = message;
        status.style.color = isError ? '#16a34a' : '#6f786d';
    }

    function requestCurrentPosition(showErrors = false) {
        if (!navigator.geolocation) {
            updatePickupStatus('Geolocalisation non supportee sur cet appareil.', true);
            updateGeoState('error', 'GPS indisponible');
            return;
        }

        updatePickupStatus('Detection de votre position en cours...');
        updateGeoState('idle', 'Recherche GPS');

        navigator.geolocation.getCurrentPosition(async (pos) => {
            const myPos = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            const details = await reverseGeocode(myPos);
            setMarker('pickup', details);
            map.setView([myPos.lat, myPos.lng], 15);
            const input = document.getElementById('pickupInput');
            if (input && details.label) input.value = details.label;
            document.getElementById('summaryPickup').textContent = details.label || 'Position detectee';
            updatePickupStatus(details.district ? `Position detectee: ${details.district}` : 'Position detectee automatiquement.');
            updateGeoState('success', 'GPS actif');
            updateProgressiveFormState();
        }, () => {
            updatePickupStatus("Position non recuperee. Autorisez la localisation ou saisissez l'adresse.", true);
            updateGeoState('error', 'GPS refuse');
            if (showErrors) alert("Impossible d'obtenir la position. Autorisez la localisation dans le navigateur.");
        }, {
            enableHighAccuracy: true,
            timeout: 12000,
            maximumAge: 0
        });
    }

    async function reverseGeocode(pos) {
        try {
            const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${pos.lng},${pos.lat}.json?access_token=${MAPBOX_TOKEN}&limit=1&language=fr&types=address,poi,neighborhood,locality,place`;
            const res = await fetch(url);
            const data = await res.json();
            if (data.features && data.features[0]) return buildAddressDetails(pos.lat, pos.lng, data.features[0]);
        } catch (e) {
            console.error('Reverse geocode error:', e);
        }

        return {
            lat: pos.lat,
            lng: pos.lng,
            label: `${pos.lat.toFixed(6)}, ${pos.lng.toFixed(6)}`,
            district: 'Brazzaville',
            addressLine: `${pos.lat.toFixed(6)}, ${pos.lng.toFixed(6)}`,
            landmark: ''
        };
    }

    function buildAddressDetails(lat, lng, feature) {
        const district = extractContextText(feature, ['neighborhood', 'locality', 'place', 'district']) || 'Brazzaville';
        const landmark = extractContextText(feature, ['poi', 'address']) || '';
        return {
            lat, lng,
            label: feature.place_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`,
            district,
            addressLine: feature.text || feature.place_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`,
            landmark
        };
    }

    function extractContextText(feature, preferredTypes) {
        if (!feature) return '';
        const context = Array.isArray(feature.context) ? feature.context : [];
        for (const type of preferredTypes) {
            if ((feature.place_type || []).includes(type) && feature.text) return feature.text;
            const hit = context.find((entry) => (entry.id || '').startsWith(type + '.'));
            if (hit && hit.text) return hit.text;
        }
        return '';
    }

    function getPickupCoordinates() {
        const lat = parseFloat(document.getElementById('p_lat').value || '0');
        const lng = parseFloat(document.getElementById('p_lng').value || '0');
        if (!lat || !lng) return null;
        return { lat, lng };
    }

    function setMarker(type, pos) {
        if (type === 'pickup') {
            if (pickupMarker) map.removeLayer(pickupMarker);
            pickupMarker = L.marker([pos.lat, pos.lng]).addTo(map);
            document.getElementById('p_lat').value = pos.lat;
            document.getElementById('p_lng').value = pos.lng;
        } else {
            if (dropoffMarker) map.removeLayer(dropoffMarker);
            dropoffMarker = L.marker([pos.lat, pos.lng]).addTo(map);
            document.getElementById('d_lat').value = pos.lat;
            document.getElementById('d_lng').value = pos.lng;
        }

        if (pickupMarker && dropoffMarker) calculateRoute();
    }

    function haversineKm(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    async function calculateRoute() {
        const pLat = parseFloat(document.getElementById('p_lat').value || '0');
        const pLng = parseFloat(document.getElementById('p_lng').value || '0');
        const dLat = parseFloat(document.getElementById('d_lat').value || '0');
        const dLng = parseFloat(document.getElementById('d_lng').value || '0');
        if (!pLat || !pLng || !dLat || !dLng) return;

        const distance = haversineKm(pLat, pLng, dLat, dLng);
        const duration = Math.max(5, (distance / 30) * 60);

        try {
            const routeUrl = `https://api.mapbox.com/directions/v5/mapbox/driving/${pLng},${pLat};${dLng},${dLat}?geometries=geojson&overview=full&language=fr&access_token=${MAPBOX_TOKEN}`;
            const res = await fetch(routeUrl);
            const data = await res.json();
            if (data.routes && data.routes[0] && data.routes[0].geometry) {
                if (routeLayer) map.removeLayer(routeLayer);
                routeLayer = L.geoJSON(data.routes[0].geometry, { style: { color: '#16a34a', weight: 5, opacity: 0.88 } }).addTo(map);
                map.fitBounds(routeLayer.getBounds(), { padding: [30, 30] });

                const distKm = (data.routes[0].distance || 0) / 1000;
                const durMin = (data.routes[0].duration || 0) / 60;
                await updateEstimates(distKm || distance, durMin || duration);
                return;
            }
        } catch (e) {
            console.warn('Mapbox directions fallback:', e);
        }

        const line = { type: 'Feature', geometry: { type: 'LineString', coordinates: [[pLng, pLat], [dLng, dLat]] } };
        if (routeLayer) map.removeLayer(routeLayer);
        routeLayer = L.geoJSON(line, { style: { color: '#16a34a', weight: 4, dashArray: '8 6' } }).addTo(map);
        map.fitBounds(routeLayer.getBounds(), { padding: [30, 30] });
        await updateEstimates(distance, duration);
    }

    async function updateEstimates(distance, duration) {
        try {
            const response = await fetch('{{ route('transport.xhr.estimate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    type: 'taxi',
                    distance: distance,
                    duration: duration
                })
            });
            const data = await response.json();

            currentEstimate.distance = Number(distance) || 0;
            currentEstimate.duration = Number(duration) || 0;
            currentEstimate.basePrice = Number(data.estimated_price || 0);
            refreshEstimatePrice();
            document.getElementById('confirmBtn').disabled = false;
        } catch (error) {
            console.error('Estimation error:', error);
        }
    }

    function refreshEstimatePrice() {
        const selectedKey = document.getElementById('selectedRideOption').value || 'eco';
        const selectedOption = RIDE_OPTIONS.find((item) => item.key === selectedKey) || RIDE_OPTIONS[0];
        const multiplier = Number(selectedOption.multiplier || 1);
        const finalPrice = Math.max(Math.round(currentEstimate.basePrice * multiplier), Math.round(PRICING.minimum_fare || 0));
        currentEstimate.finalPrice = finalPrice;

        const estimateSection = document.getElementById('estimateSection');
        estimateSection.classList.add('is-visible');
        document.getElementById('estDistance').textContent = `${currentEstimate.distance.toFixed(1)} km`;
        document.getElementById('estDuration').textContent = `${Math.ceil(currentEstimate.duration)} min`;
        document.getElementById('estDistanceMap').textContent = `${currentEstimate.distance.toFixed(1)} km`;
        document.getElementById('estDurationMap').textContent = `${Math.ceil(currentEstimate.duration)} min`;
        document.getElementById('estBaseFare').textContent = `${Number(PRICING.minimum_fare || 0).toLocaleString('fr-FR')} FCFA`;
        document.getElementById('estPrice').textContent = `${finalPrice.toLocaleString('fr-FR')} FCFA`;
        document.getElementById('estPriceMap').textContent = `${finalPrice.toLocaleString('fr-FR')} FCFA`;
        document.getElementById('heroConfirmPrice').textContent = `${finalPrice.toLocaleString('fr-FR')} F`;
        document.getElementById('selectedRideMeta').textContent = `${selectedOption.name} x${multiplier.toFixed(2)} · Base ${Number(currentEstimate.basePrice || 0).toLocaleString('fr-FR')} FCFA`;
        document.getElementById('estimatedDistance').value = currentEstimate.distance.toFixed(2);
        document.getElementById('estimatedDuration').value = Math.ceil(currentEstimate.duration);
        document.getElementById('estimatedPriceValue').value = finalPrice;
        const formulaLabel = document.getElementById('heroSelectedFormula');
        if (formulaLabel) formulaLabel.textContent = selectedOption.name;

        RIDE_OPTIONS.forEach((option) => {
            const node = document.getElementById(`ridePrice${option.key.charAt(0).toUpperCase() + option.key.slice(1)}`);
            if (!node) return;
            const optionPrice = Math.max(Math.round(currentEstimate.basePrice * Number(option.multiplier || 1)), Math.round(PRICING.minimum_fare || 0));
            node.textContent = `${optionPrice.toLocaleString('fr-FR')} FCFA`;
        });
    }

    document.addEventListener('change', (event) => {
        if (event.target.matches('input[name="payment_method"]')) {
            document.querySelectorAll('.taxi-pay-row .taxi-mode-card').forEach((card) => card.classList.toggle('is-selected', card.querySelector('input')?.checked));
        }
        if (event.target.matches('input[name="ride_timing"]')) {
            document.querySelectorAll('.taxi-mode-row .taxi-mode-card').forEach((card) => card.classList.toggle('is-selected', card.querySelector('input')?.checked));
        }
    });

    document.getElementById('confirmBtn').addEventListener('click', async function() {
        const btn = this;

        if (!TRANSPORT_AUTHENTICATED) {
            window.location.href = LOGIN_URL;
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creation de la course...';

        const pickupInput = document.getElementById('pickupInput').value.trim();
        const dropoffInput = document.getElementById('dropoffInput').value.trim();
        const pickupLat = document.getElementById('p_lat').value;
        const pickupLng = document.getElementById('p_lng').value;
        const dropoffLat = document.getElementById('d_lat').value;
        const dropoffLng = document.getElementById('d_lng').value;
        const pickupNote = document.getElementById('pickupNote').value.trim();
        const dropoffNote = document.getElementById('dropoffNote').value.trim();
        const timing = document.querySelector('input[name="ride_timing"]:checked')?.value || 'now';
        const scheduledAt = document.getElementById('scheduledAtInput').value;
        const passengerCount = document.getElementById('passengerCount').value;
        const rideOption = document.getElementById('selectedRideOption').value || 'eco';
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'cash';

        if (!pickupInput || !dropoffInput || !pickupLat || !pickupLng || !dropoffLat || !dropoffLng) {
            btn.disabled = false;
            btn.textContent = 'Confirmer la course';
            alert('Definissez le depart et la destination avec la recherche, le GPS ou les reperes sur la carte.');
            return;
        }

        if (timing === 'later' && !scheduledAt) {
            btn.disabled = false;
            btn.textContent = 'Confirmer la course';
            alert('Choisissez une date et une heure si vous programmez la course.');
            return;
        }

        const payload = {
            type: 'taxi',
            pickup_address: pickupNote ? `${pickupInput} | Repere: ${pickupNote}` : pickupInput,
            pickup_lat: pickupLat,
            pickup_lng: pickupLng,
            dropoff_address: dropoffNote ? `${dropoffInput} | Repere: ${dropoffNote}` : dropoffInput,
            dropoff_lat: dropoffLat,
            dropoff_lng: dropoffLng,
            estimated_distance: document.getElementById('estimatedDistance').value || null,
            estimated_duration: document.getElementById('estimatedDuration').value || null,
            estimated_price: document.getElementById('estimatedPriceValue').value || null,
            total_price: document.getElementById('estimatedPriceValue').value || null,
            scheduled_at: timing === 'later' ? scheduledAt : null,
            notes: JSON.stringify({
                pickup_note: pickupNote,
                dropoff_note: dropoffNote,
                ride_option: rideOption,
                passenger_count: passengerCount,
                timing: timing
            }),
            payment_method: paymentMethod
        };

        try {
            const response = await fetch('{{ route('transport.xhr.bookings.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (response.ok && data.booking) {
                window.location.href = `/transport/booking/${data.booking.uuid}`;
                return;
            }

            const message = data.message || data.error?.message || 'Erreur lors de la reservation. Veuillez reessayer.';
            alert(message);
        } catch (error) {
            console.error('Booking error:', error);
            alert('Erreur reseau lors de la reservation. Veuillez reessayer.');
        }

        btn.disabled = false;
        btn.innerHTML = 'Confirmer la course <span class="taxi-confirm__price" id="heroConfirmPrice">' + (currentEstimate.finalPrice ? currentEstimate.finalPrice.toLocaleString('fr-FR') + ' F' : '{{ number_format((float) ($pricingData['minimum_fare'] ?? 0), 0, ',', ' ') }} F') + '</span>';
    });
</script>
@endsection
