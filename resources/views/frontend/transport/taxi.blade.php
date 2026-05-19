@extends('frontend.layouts.transport')
@section('hide_module_header', true)
@section('hide_module_footer', true)
@php
    $homeContent = $homeContent ?? [];
    $resolveHomeMedia = static function (?string $path): ?string {
        if (blank($path)) {
            return null;
        }

        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
            ? $path
            : asset(ltrim($path, '/'));
    };
    $transportHeroVisual = $resolveHomeMedia($homeContent['hero_transport_image'] ?? null);
    $transportHeroStyle = $transportHeroVisual
        ? "background-image:linear-gradient(135deg, rgba(255,255,255,.96) 0%, rgba(255,255,255,.88) 38%, rgba(255,255,255,.68) 100%), url('{$transportHeroVisual}'); background-size:cover; background-position:center;"
        : null;
    $transportHeroBadge = 'Brazzaville · Pointe-Noire · Congo';
    $transportHeroDescription = $homeContent['hero_description'] ?? 'Saisissez le depart et la destination. Kende calcule le trajet et vous laisse confirmer en toute clarte.';
    $transportSupportTitle = $homeContent['support_title'] ?? 'Besoin d aide avant de confirmer ?';
    $transportSupportDescription = $homeContent['support_description'] ?? 'Consultez l aide Kende ou contactez le support pour les zones, les tarifs et les reservations en attente.';
    $transportSupportCta = $homeContent['support_cta_text'] ?? 'Contacter le support';
    $transportTestimonials = collect([1, 2, 3])->map(function ($index) use ($homeContent) {
        return [
            'tag' => $homeContent['testimonial_' . $index . '_tag'] ?? null,
            'quote' => $homeContent['testimonial_' . $index . '_quote'] ?? null,
            'name' => $homeContent['testimonial_' . $index . '_name'] ?? null,
            'loc' => $homeContent['testimonial_' . $index . '_loc'] ?? null,
        ];
    })->filter(fn ($item) => filled($item['quote']))->values();
    $transportOpportunities = collect([1, 2, 3])->map(function ($index) use ($homeContent, $resolveHomeMedia) {
        return [
            'title' => $homeContent['opportunity_' . $index . '_title'] ?? null,
            'body' => $homeContent['opportunity_' . $index . '_body'] ?? null,
            'cta' => $homeContent['opportunity_' . $index . '_cta'] ?? null,
            'url' => $homeContent['opportunity_' . $index . '_url'] ?? null,
            'image' => $resolveHomeMedia($homeContent['opportunity_' . $index . '_image'] ?? null),
        ];
    })->filter(fn ($item) => filled($item['title']) && filled($item['body']) && filled($item['url']))->values();
@endphp

@section('title', 'Taxi | Kende')
@section('description', $transportHeroDescription)

@php
    $pricingData = [
        'base_fare'         => (float) ($pricing->base_fare         ?? 500),
        'price_per_km'      => (float) ($pricing->price_per_km      ?? 200),
        'price_per_minute'  => (float) ($pricing->price_per_minute  ?? 50),
        'minimum_fare'      => (float) ($pricing->minimum_fare      ?? 1000),
        'surge_multiplier'  => (float) ($pricing->surge_multiplier  ?? 1),
    ];

    $rideOptions = [
        ['key' => 'eco',     'name' => 'Eco',     'description' => 'Pour le quotidien',     'multiplier' => 1,    'base_label' => 'Le plus leger'],
        ['key' => 'comfort', 'name' => 'Confort', 'description' => 'Plus d espace',         'multiplier' => 1.18, 'base_label' => 'Plus de confort'],
        ['key' => 'xl',      'name' => 'XL',      'description' => 'Groupe ou bagages',     'multiplier' => 1.35, 'base_label' => 'Pour plusieurs passagers'],
    ];

    $transportHomeUrl = route('transport.index');
    $faqUrl           = route('faq');
    $privacyUrl       = route('privacy.policy', ['brand' => 'kende']);
    $contactUrl       = route('contact.us', ['brand' => 'kende']);
    $taxiUrl          = route('transport.taxi');
    $carpoolUrl       = route('transport.carpool');
    $rentalUrl        = route('transport.rental');
    $busUrl           = route('transport.bus');
    $bookingsUrl      = auth()->check()
        ? route('transport.my_bookings')
        : route('user.login', ['redirect' => route('transport.my_bookings')]);
@endphp

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<style>
/* ====================================================
   KENDE TAXI — design tokens
   ==================================================== */
:root{
    --k-or:      #FF6B00;
    --k-or-10:   rgba(255,107,0,.10);
    --k-or-20:   rgba(255,107,0,.20);
    --k-or-sh:   rgba(255,107,0,.24);
    --k-gr:      #009B3A;
    --k-gr-10:   rgba(0,155,58,.10);
    --k-ink:     #111113;
    --k-t2:      #444447;
    --k-t3:      #888890;
    --k-bg:      #F5F5F7;
    --k-bg2:     #EBEBED;
    --k-white:   #FFFFFF;
    --k-line:    #E4E4E6;
    --k-red:     #DC241F;

    --r-sm: 8px;
    --r-md: 14px;
    --r-lg: 22px;
    --r-xl: 32px;

    --sh-card: 0 16px 48px rgba(17,17,19,.09);
    --sh-sm:   0 4px 16px rgba(17,17,19,.06);
}

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

body.bd-transport-shell{
    background:var(--k-bg);
    color:var(--k-ink);
    font-family:'Plus Jakarta Sans','Outfit',system-ui,sans-serif;
    -webkit-font-smoothing:antialiased;
}

/* ====================================================
   NAV
   ==================================================== */
.ktx-nav{
    position:sticky;
    top:0;
    z-index:50;
    background:rgba(255,255,255,.96);
    backdrop-filter:blur(16px);
    border-bottom:1px solid var(--k-line);
}
.ktx-nav__inner{
    max-width:1400px;
    margin:0 auto;
    padding:0 24px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    height:68px;
}
.ktx-brand{
    display:inline-flex;
    align-items:center;
    gap:8px;
    text-decoration:none;
    font-family:'Outfit',sans-serif;
    font-weight:900;
    font-size:1.3rem;
    letter-spacing:-.03em;
    color:var(--k-ink);
}
.ktx-brand em{color:var(--k-or);font-style:normal}
.ktx-brand__dot{
    width:8px;height:8px;border-radius:50%;
    background:var(--k-gr);
    box-shadow:0 0 0 5px var(--k-gr-10);
}
.ktx-links{
    display:flex;align-items:center;gap:4px;
}
.ktx-links a{
    display:inline-flex;align-items:center;
    height:40px;padding:0 14px;
    border-radius:999px;
    font-size:.9rem;font-weight:700;
    color:var(--k-t2);
    text-decoration:none;
    transition:background .15s,color .15s;
}
.ktx-links a:hover,.ktx-links a.is-active{
    background:var(--k-or-10);
    color:var(--k-or);
}
.ktx-actions{display:flex;align-items:center;gap:8px}
.ktx-btn{
    display:inline-flex;align-items:center;justify-content:center;
    height:40px;padding:0 18px;
    border-radius:999px;
    font-size:.88rem;font-weight:800;
    text-decoration:none;
    border:none;cursor:pointer;
}
.ktx-btn--ghost{
    background:var(--k-white);
    border:1.5px solid var(--k-line);
    color:var(--k-ink);
}
.ktx-btn--primary{
    background:var(--k-or);
    color:#fff;
    box-shadow:0 6px 18px var(--k-or-sh);
}

/* ====================================================
   PAGE SHELL
   ==================================================== */
.ktx-page{
    max-width:1400px;
    margin:0 auto;
    padding:0 24px 60px;
}

/* ====================================================
   TOP STRIP — Congo flag
   ==================================================== */
.ktx-flag{display:flex;height:3px;width:100%}
.ktx-flag span{flex:1}

/* ====================================================
   HERO — minimal, focused on the task
   ==================================================== */
.ktx-hero{
    text-align:center;
    padding:48px 0 32px;
    border:1px solid transparent;
    border-radius:28px;
    margin-bottom:18px;
    overflow:hidden;
}
.ktx-hero__tag{
    display:inline-flex;align-items:center;gap:7px;
    background:var(--k-or-10);
    border:1px solid rgba(255,107,0,.22);
    border-radius:999px;
    padding:5px 14px;
    font-size:.76rem;font-weight:700;
    color:var(--k-or);
    letter-spacing:.06em;
    text-transform:uppercase;
    margin-bottom:16px;
}
.ktx-hero__tag-dot{width:6px;height:6px;border-radius:50%;background:var(--k-or)}
.ktx-hero h1{
    font-size:clamp(1.7rem,3.5vw,2.8rem);
    font-weight:900;
    letter-spacing:-.04em;
    color:var(--k-ink);
    margin-bottom:10px;
}
.ktx-hero h1 em{color:var(--k-or);font-style:normal}
.ktx-hero p{
    font-size:.97rem;
    color:var(--k-t2);
    max-width:460px;
    margin:0 auto;
    line-height:1.65;
}
.ktx-support-strip{
    margin:0 0 18px;
    padding:14px 18px;
    border:1px solid var(--k-line);
    border-radius:18px;
    background:linear-gradient(180deg,#fff 0%,#fafafc 100%);
    box-shadow:var(--sh-sm);
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:18px;
}
.ktx-support-strip__eyebrow{
    font-size:.7rem;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.12em;
    color:var(--k-t3);
    margin-bottom:6px;
}
.ktx-support-strip__title{
    font-size:1rem;
    font-weight:800;
    color:var(--k-ink);
    margin-bottom:4px;
}
.ktx-support-strip__body{
    color:var(--k-t2);
    font-size:.92rem;
    line-height:1.65;
    max-width:760px;
}
.ktx-support-strip__cta{flex-shrink:0}
.ktx-proof-grid,
.ktx-op-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:16px;
}
.ktx-proof-grid{margin:18px 0 22px}
.ktx-proof-card,
.ktx-op-card{
    border:1px solid var(--k-line);
    border-radius:18px;
    background:#fff;
    box-shadow:var(--sh-sm);
}
.ktx-proof-card{padding:16px 16px 14px}
.ktx-proof-card__tag,
.ktx-op-section__eyebrow{
    font-size:.68rem;
    font-weight:800;
    letter-spacing:.12em;
    text-transform:uppercase;
    color:var(--k-t3);
}
.ktx-proof-card__quote{
    margin:10px 0 12px;
    color:var(--k-ink);
    line-height:1.65;
    font-size:.92rem;
}
.ktx-proof-card__meta{color:var(--k-t2);font-size:.8rem}
.ktx-op-section{
    margin:0 0 22px;
    padding:18px;
    border:1px solid var(--k-line);
    border-radius:22px;
    background:linear-gradient(180deg,#fff 0%,#fafafc 100%);
}
.ktx-op-section__head{
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    gap:18px;
    margin-bottom:14px;
}
.ktx-op-section__title{
    font-size:1.18rem;
    font-weight:900;
    color:var(--k-ink);
    letter-spacing:-.03em;
}
.ktx-op-section__body{
    color:var(--k-t2);
    font-size:.92rem;
    line-height:1.65;
    max-width:720px;
}
.ktx-op-card{padding:16px; overflow:hidden}
.ktx-op-card__media{margin:-16px -16px 14px; aspect-ratio:16/9; background:var(--k-bg2)}
.ktx-op-card__media img{width:100%; height:100%; object-fit:cover; display:block}
.ktx-op-card__title{font-weight:800;color:var(--k-ink);margin-bottom:8px}
.ktx-op-card__body{color:var(--k-t2);font-size:.88rem;line-height:1.65;margin-bottom:12px}
.ktx-op-card__cta{color:var(--k-or);font-weight:800;text-decoration:none}

/* ====================================================
   MAIN BOOKING LAYOUT — form left / map right
   ==================================================== */
.ktx-booking{
    display:grid;
    grid-template-columns:420px 1fr;
    gap:20px;
    align-items:start;
}

/* ====================================================
   BOOKING PANEL (left)
   ==================================================== */
.ktx-panel{
    position:sticky;
    top:88px;
    display:flex;
    flex-direction:column;
    gap:12px;
}

.ktx-card{
    background:var(--k-white);
    border:1px solid var(--k-line);
    border-radius:var(--r-lg);
    box-shadow:var(--sh-sm);
    padding:20px;
}

/* ---- Route picker ---- */
.ktx-route{
    display:flex;
    flex-direction:column;
    gap:0;
}

.ktx-route-row{
    display:flex;
    align-items:flex-start;
    gap:12px;
    position:relative;
}

.ktx-route-row + .ktx-route-row{
    margin-top:4px;
}

/* Connector between dots */
.ktx-route-connector{
    display:flex;
    align-items:flex-start;
    padding:0 0 0 10px;
    gap:12px;
}
.ktx-route-line{
    width:2px;
    height:32px;
    background:repeating-linear-gradient(
        to bottom,
        var(--k-or) 0,
        var(--k-or) 4px,
        transparent 4px,
        transparent 8px
    );
    margin:0 auto;
    flex-shrink:0;
}

.ktx-dot{
    width:12px;height:12px;
    border-radius:50%;
    border:2.5px solid #fff;
    box-shadow:0 0 0 2px currentColor;
    flex-shrink:0;
    margin-top:14px;
}
.ktx-dot--from{color:var(--k-or);background:var(--k-or)}
.ktx-dot--to{color:var(--k-gr);background:var(--k-gr)}

.ktx-input-wrap{
    flex:1;
    position:relative;
}

.ktx-input-label{
    display:block;
    font-size:.75rem;
    font-weight:800;
    color:var(--k-t3);
    letter-spacing:.06em;
    text-transform:uppercase;
    margin-bottom:5px;
}

.ktx-input{
    width:100%;
    height:48px;
    border:1.5px solid var(--k-line);
    border-radius:var(--r-md);
    background:var(--k-bg);
    padding:0 14px;
    font-size:.92rem;
    font-weight:600;
    color:var(--k-ink);
    font-family:inherit;
    outline:none;
    transition:border-color .15s,background .15s,box-shadow .15s;
}
.ktx-input:focus{
    border-color:var(--k-or);
    background:var(--k-white);
    box-shadow:0 0 0 3px var(--k-or-10);
}
.ktx-input::placeholder{color:var(--k-t3);font-weight:500}

/* Suggestions dropdown */
.kende-suggestions{
    position:absolute;
    left:0;right:0;
    top:calc(100% + 4px);
    background:var(--k-white);
    border:1.5px solid var(--k-line);
    border-radius:var(--r-md);
    box-shadow:var(--sh-card);
    z-index:60;
    overflow:hidden;
    display:none;
    max-height:220px;
    overflow-y:auto;
}
.kende-suggestions.is-visible{display:block}
.kende-suggestion{
    display:block;
    width:100%;
    border:none;
    background:transparent;
    text-align:left;
    padding:11px 14px;
    font-size:.88rem;
    color:var(--k-t2);
    cursor:pointer;
    font-family:inherit;
    transition:background .12s;
}
.kende-suggestion:hover{background:var(--k-bg)}

/* Status text */
.ktx-status{
    font-size:.78rem;
    color:var(--k-t3);
    margin-top:4px;
    min-height:18px;
}

/* ---- Action row (buttons) ---- */
.ktx-action-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px;
    margin-top:4px;
}
.ktx-act-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    height:44px;
    border-radius:var(--r-md);
    font-size:.85rem;
    font-weight:700;
    font-family:inherit;
    cursor:pointer;
    transition:all .15s;
    border:none;
}
.ktx-act-btn--locate{
    background:var(--k-bg);
    border:1.5px solid var(--k-line);
    color:var(--k-t2);
}
.ktx-act-btn--locate:hover{background:var(--k-bg2)}
.ktx-act-btn--calc{
    background:var(--k-ink);
    color:#fff;
}
.ktx-act-btn--calc:hover{background:#2a2a2c}

/* ---- Ride options ---- */
.ktx-options-label{
    font-size:.75rem;
    font-weight:800;
    letter-spacing:.08em;
    text-transform:uppercase;
    color:var(--k-t3);
    margin-bottom:10px;
}
.ktx-options-grid{
    display:flex;
    flex-direction:column;
    gap:8px;
}
.ktx-opt{
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px 14px;
    border:1.5px solid var(--k-line);
    border-radius:var(--r-md);
    background:var(--k-bg);
    cursor:pointer;
    transition:border-color .15s,background .15s,box-shadow .15s;
    text-align:left;
    font-family:inherit;
}
.ktx-opt.is-active{
    background:var(--k-white);
    border-color:var(--k-or);
    box-shadow:0 0 0 3px var(--k-or-10);
}
.ktx-opt__icon{
    width:36px;height:36px;
    border-radius:var(--r-sm);
    background:var(--k-bg2);
    display:flex;align-items:center;justify-content:center;
    font-size:1rem;
    flex-shrink:0;
    transition:background .15s;
}
.ktx-opt.is-active .ktx-opt__icon{background:var(--k-or-10)}
.ktx-opt__body{flex:1}
.ktx-opt__name{
    font-size:.9rem;
    font-weight:800;
    color:var(--k-ink);
    margin-bottom:2px;
}
.ktx-opt.is-active .ktx-opt__name{color:var(--k-or)}
.ktx-opt__desc{
    font-size:.78rem;
    color:var(--k-t3);
}
.ktx-opt__check{
    width:20px;height:20px;
    border-radius:50%;
    border:2px solid var(--k-line);
    display:flex;align-items:center;justify-content:center;
    flex-shrink:0;
    color:transparent;
    font-size:.65rem;
    font-weight:900;
    transition:all .15s;
}
.ktx-opt.is-active .ktx-opt__check{
    background:var(--k-or);
    border-color:var(--k-or);
    color:#fff;
}

/* ---- Estimate section (hidden until route calculated) ---- */
.ktx-estimate{
    background:var(--k-or-10);
    border:1px solid rgba(255,107,0,.2);
    border-radius:var(--r-md);
    padding:14px 16px;
}
.ktx-estimate__head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:10px;
}
.ktx-estimate__label{
    font-size:.72rem;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:var(--k-or);
    margin-bottom:3px;
}
.ktx-estimate__meta{
    font-size:.8rem;
    color:var(--k-t2);
}
.ktx-estimate__price{
    font-size:1.3rem;
    font-weight:900;
    color:var(--k-ink);
    white-space:nowrap;
}
.ktx-stats{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:8px;
}
.ktx-stat{
    background:var(--k-white);
    border-radius:var(--r-sm);
    padding:8px 10px;
    border:1px solid rgba(255,107,0,.12);
}
.ktx-stat__k{
    font-size:.68rem;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.06em;
    color:var(--k-t3);
    margin-bottom:3px;
}
.ktx-stat__v{
    font-size:.88rem;
    font-weight:800;
    color:var(--k-ink);
}

/* ---- Collapsible extra options ---- */
.ktx-details-toggle{
    display:flex;
    align-items:center;
    justify-content:space-between;
    width:100%;
    background:none;
    border:none;
    cursor:pointer;
    font-family:inherit;
    font-size:.88rem;
    font-weight:700;
    color:var(--k-t2);
    padding:4px 0;
}
.ktx-details-toggle__icon{
    font-size:.75rem;
    color:var(--k-t3);
    transition:transform .2s;
}
.ktx-details-toggle[aria-expanded="true"] .ktx-details-toggle__icon{
    transform:rotate(180deg);
}
.ktx-details-body{
    display:none;
    flex-direction:column;
    gap:12px;
    padding-top:14px;
}
.ktx-details-body.is-open{display:flex}

.ktx-field-label{
    display:block;
    font-size:.76rem;
    font-weight:700;
    color:var(--k-t3);
    margin-bottom:5px;
}
.ktx-field-input{
    width:100%;
    height:44px;
    border:1.5px solid var(--k-line);
    border-radius:var(--r-md);
    background:var(--k-bg);
    padding:0 12px;
    font-size:.88rem;
    font-weight:600;
    color:var(--k-ink);
    font-family:inherit;
    outline:none;
    transition:border-color .15s,box-shadow .15s;
}
.ktx-field-input:focus{
    border-color:var(--k-or);
    box-shadow:0 0 0 3px var(--k-or-10);
}
.ktx-textarea{
    width:100%;
    min-height:70px;
    border:1.5px solid var(--k-line);
    border-radius:var(--r-md);
    background:var(--k-bg);
    padding:10px 12px;
    font-size:.86rem;
    font-weight:500;
    color:var(--k-ink);
    font-family:inherit;
    outline:none;
    resize:vertical;
    transition:border-color .15s,box-shadow .15s;
}
.ktx-textarea:focus{
    border-color:var(--k-or);
    box-shadow:0 0 0 3px var(--k-or-10);
}
.ktx-grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px}

/* Chip selectors (timing / paiement) */
.ktx-chips{display:flex;gap:8px;flex-wrap:wrap}
.kende-chip{
    position:relative;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    height:40px;
    padding:0 16px;
    border-radius:999px;
    border:1.5px solid var(--k-line);
    background:var(--k-bg);
    font-size:.86rem;
    font-weight:700;
    color:var(--k-t2);
    cursor:pointer;
    font-family:inherit;
    transition:all .15s;
}
.kende-chip input{
    position:absolute;inset:0;
    opacity:0;cursor:pointer;
}
.kende-chip.is-selected{
    background:var(--k-white);
    border-color:rgba(255,107,0,.3);
    color:var(--k-or);
    box-shadow:0 0 0 3px var(--k-or-10);
}

/* ---- CTA confirm ---- */
.ktx-confirm{
    width:100%;
    height:56px;
    border:none;
    border-radius:var(--r-md);
    background:var(--k-or);
    color:#fff;
    font-size:.96rem;
    font-weight:800;
    font-family:inherit;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 20px;
    box-shadow:0 10px 28px var(--k-or-sh);
    transition:transform .15s,box-shadow .15s;
}
.ktx-confirm:hover:not(:disabled){
    transform:translateY(-1px);
    box-shadow:0 14px 36px var(--k-or-sh);
}
.ktx-confirm:disabled{
    opacity:.5;
    cursor:not-allowed;
    box-shadow:none;
}
.ktx-confirm__price{
    font-size:.9rem;
    opacity:.88;
    white-space:nowrap;
}

/* ====================================================
   MAP SECTION (right col)
   ==================================================== */
.ktx-map-col{
    position:sticky;
    top:88px;
}
.ktx-map-card{
    background:var(--k-white);
    border:1px solid var(--k-line);
    border-radius:var(--r-lg);
    overflow:hidden;
    box-shadow:var(--sh-sm);
}
.ktx-map-topbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:14px 18px;
    border-bottom:1px solid var(--k-line);
    background:var(--k-white);
}
.ktx-map-topbar__info{}
.ktx-map-topbar__title{
    font-size:.9rem;
    font-weight:800;
    color:var(--k-ink);
    margin-bottom:2px;
}
.ktx-map-topbar__sub{
    font-size:.78rem;
    color:var(--k-t3);
}
.ktx-geo-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    height:32px;
    padding:0 12px;
    border-radius:999px;
    background:var(--k-bg);
    border:1px solid var(--k-line);
    font-size:.76rem;
    font-weight:700;
    color:var(--k-t3);
}
.ktx-geo-dot{
    width:7px;height:7px;
    border-radius:50%;
    background:var(--k-t3);
}
[data-state="ready"]  .ktx-geo-dot{background:var(--k-gr)}
[data-state="loading"].ktx-geo-dot{background:var(--k-or)}
[data-state="error"]  .ktx-geo-dot{background:var(--k-red)}

/* Map canvas */
.ktx-map-canvas{
    position:relative;
    height:calc(100vh - 260px);
    min-height:480px;
    background:#dfe8f0;
}
#taxiMap{
    position:absolute;
    inset:0;
    z-index:1;
}
/* Empty state overlay */
.kende-map__empty{
    position:absolute;
    inset:0;
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:3;
    pointer-events:none;
}
.ktx-map-empty-card{
    border-radius:var(--r-lg);
    background:rgba(255,255,255,.94);
    border:1px solid var(--k-line);
    box-shadow:var(--sh-card);
    padding:24px 28px;
    min-width:240px;
    text-align:center;
}
.ktx-map-empty-icon{
    width:52px;height:52px;
    margin:0 auto 12px;
    border-radius:50%;
    background:var(--k-gr-10);
    display:flex;align-items:center;justify-content:center;
    font-size:1.4rem;
    color:var(--k-gr);
}
.ktx-map-empty-title{
    font-size:.96rem;
    font-weight:800;
    color:var(--k-ink);
    margin-bottom:6px;
}
.ktx-map-empty-text{
    font-size:.82rem;
    color:var(--k-t3);
    line-height:1.55;
}

/* Map controls bar */
.kende-map__controls{
    position:absolute;
    bottom:14px;left:14px;
    z-index:5;
    display:flex;gap:6px;flex-wrap:wrap;
}
.kende-map__ctrl{
    height:36px;
    border:none;
    border-radius:999px;
    padding:0 14px;
    background:rgba(17,17,19,.80);
    color:#fff;
    font-size:.8rem;
    font-weight:700;
    cursor:pointer;
    font-family:inherit;
}
.kende-map__ctrl.is-active{background:var(--k-or)}

/* Map summary strip */
.ktx-map-summary{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:1px;
    background:var(--k-line);
    border-top:1px solid var(--k-line);
}
.ktx-map-summary-cell{
    background:var(--k-white);
    padding:10px 14px;
}
.ktx-map-summary-k{
    font-size:.68rem;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.06em;
    color:var(--k-t3);
    margin-bottom:3px;
}
.ktx-map-summary-v{
    font-size:.85rem;
    font-weight:800;
    color:var(--k-ink);
}

/* ====================================================
   FOOTER
   ==================================================== */
.ktx-footer{
    margin-top:48px;
    padding:20px 0;
    border-top:1px solid var(--k-line);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    flex-wrap:wrap;
    color:var(--k-t3);
    font-size:.84rem;
}
.ktx-footer-links{
    display:flex;align-items:center;gap:14px;flex-wrap:wrap;
}
.ktx-footer a{color:inherit;text-decoration:none}
.ktx-footer a:hover{color:var(--k-or)}

/* ====================================================
   RESPONSIVE
   ==================================================== */
@media(max-width:1080px){
    .ktx-booking{
        grid-template-columns:1fr;
    }
    .ktx-support-strip{
        flex-direction:column;
        align-items:flex-start;
    }
    .ktx-proof-grid,
    .ktx-op-grid{
        grid-template-columns:1fr;
    }
    .ktx-op-section__head{
        flex-direction:column;
        align-items:flex-start;
    }
    .ktx-panel,.ktx-map-col{
        position:relative;
        top:0;
    }
    .ktx-map-canvas{
        height:55vw;
        min-height:340px;
    }
    /* Map comes first on mobile */
    .ktx-map-col{order:-1}
}
@media(max-width:640px){
    .ktx-nav__inner{padding:0 16px}
    .ktx-links{display:none}
    .ktx-page{padding:0 14px 48px}
    .ktx-hero{padding:32px 0 20px}
    .ktx-grid2{grid-template-columns:1fr}
    .ktx-map-summary{grid-template-columns:1fr 1fr}
}
</style>
@endsection

@section('content')

{{-- Congo flag strip --}}
<div class="ktx-flag">
    <span style="background:#009B3A;"></span>
    <span style="background:#FBCE07;"></span>
    <span style="background:#DC241F;"></span>
</div>

{{-- NAV --}}
<nav class="ktx-nav">
    <div class="ktx-nav__inner">
        <a href="{{ $transportHomeUrl }}" class="ktx-brand">Ken<em>de</em><span class="ktx-brand__dot"></span></a>
        <div class="ktx-links">
            <a href="{{ $taxiUrl }}" class="is-active">Taxi</a>
            <a href="{{ $carpoolUrl }}">Covoiturage</a>
            <a href="{{ $rentalUrl }}">Location</a>
            <a href="{{ $busUrl }}">Bus</a>
        </div>
        <div class="ktx-actions">
            <a href="{{ $bookingsUrl }}" class="ktx-btn ktx-btn--ghost">Mes reservations</a>
            <a href="{{ $taxiUrl }}" class="ktx-btn ktx-btn--primary">Reserver</a>
        </div>
    </div>
</nav>

<div class="ktx-page">

    {{-- HERO --}}
    <div class="ktx-hero{{ $transportHeroVisual ? ' ktx-hero--with-media' : '' }}" @if($transportHeroStyle) style="{{ $transportHeroStyle }}" @endif>
        <div class="ktx-hero__tag">
            <span class="ktx-hero__tag-dot"></span>
            {{ $transportHeroBadge }}
        </div>
        <h1>Reservez votre <em>taxi</em></h1>
        <p>{{ $transportHeroDescription }}</p>
    </div>

    <div class="ktx-support-strip">
        <div>
            <div class="ktx-support-strip__eyebrow">Support Kende</div>
            <div class="ktx-support-strip__title">{{ $transportSupportTitle }}</div>
            <div class="ktx-support-strip__body">{{ $transportSupportDescription }}</div>
        </div>
        <a href="{{ $contactUrl }}" class="ktx-btn ktx-btn--primary ktx-support-strip__cta">{{ $transportSupportCta }}</a>
    </div>

    {{-- BOOKING + MAP --}}
    <div class="ktx-booking">

        {{-- LEFT — BOOKING PANEL --}}
        <aside class="ktx-panel">

            {{-- CARD 1 — Route picker --}}
            <div class="ktx-card">
                <form id="taxiBookingForm" method="POST" action="#" onsubmit="return false;">
                    @csrf
                    <input type="hidden" id="p_lat"               name="pickup_lat">
                    <input type="hidden" id="p_lng"               name="pickup_lng">
                    <input type="hidden" id="d_lat"               name="dropoff_lat">
                    <input type="hidden" id="d_lng"               name="dropoff_lng">
                    <input type="hidden" id="estimatedDistance"   name="estimated_distance">
                    <input type="hidden" id="estimatedDuration"   name="estimated_duration">
                    <input type="hidden" id="estimatedPriceValue" name="estimated_price">
                    <input type="hidden" id="selectedRideOption"  name="ride_option" value="eco">

                    {{-- Route input group --}}
                    <div class="ktx-route" style="margin-bottom:14px;">

                        {{-- Depart --}}
                        <div class="ktx-route-row">
                            <span class="ktx-dot ktx-dot--from"></span>
                            <div class="ktx-input-wrap">
                                <label class="ktx-input-label" for="pickupInput">Depart</label>
                                <input id="pickupInput"
                                       name="pickup_address"
                                       class="ktx-input"
                                       type="text"
                                       placeholder="D'ou partez-vous ?"
                                       autocomplete="off">
                                <div id="pickupSuggestions" class="kende-suggestions"></div>
                                <div id="pickupStatus" class="ktx-status">Saisissez votre depart ou utilisez votre position.</div>
                            </div>
                        </div>

                        {{-- Connecting line --}}
                        <div class="ktx-route-connector">
                            <div class="ktx-route-line"></div>
                        </div>

                        {{-- Destination --}}
                        <div class="ktx-route-row">
                            <span class="ktx-dot ktx-dot--to"></span>
                            <div class="ktx-input-wrap">
                                <label class="ktx-input-label" for="dropoffInput">Destination</label>
                                <input id="dropoffInput"
                                       name="dropoff_address"
                                       class="ktx-input"
                                       type="text"
                                       placeholder="Ou allez-vous ?"
                                       autocomplete="off">
                                <div id="dropoffSuggestions" class="kende-suggestions"></div>
                                <div id="dropoffStatus" class="ktx-status">Definissez clairement votre destination.</div>
                            </div>
                        </div>
                    </div>

                    {{-- Action buttons --}}
                    <div class="ktx-action-row" style="margin-bottom:16px;">
                        <button id="locateMeBtn" type="button" class="ktx-act-btn ktx-act-btn--locate">
                            <i class="fas fa-location-arrow"></i> Ma position
                        </button>
                        <button id="heroSearchBtn" type="button" class="ktx-act-btn ktx-act-btn--calc">
                            <i class="fas fa-route"></i> Calculer le trajet
                        </button>
                    </div>

                    {{-- Ride options --}}
                    <div style="margin-bottom:14px;">
                        <div class="ktx-options-label">Categorie de vehicule</div>
                        <div class="ktx-options-grid">
                                    @php
                                $optSvgs = [
                                    'eco' => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 13 L5 8 L15 8 L17 13 Z" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linejoin="round"/><circle cx="6.5" cy="14.5" r="1.8" stroke="currentColor" stroke-width="1.3"/><circle cx="13.5" cy="14.5" r="1.8" stroke="currentColor" stroke-width="1.3"/></svg>',
                                    'comfort' => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 13 L5 7 L15 7 L18 13 Z" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linejoin="round"/><circle cx="6" cy="14.5" r="1.8" stroke="currentColor" stroke-width="1.3"/><circle cx="14" cy="14.5" r="1.8" stroke="currentColor" stroke-width="1.3"/><path d="M7 7 L8 4.5 L12 4.5 L13 7" stroke="currentColor" stroke-width="1.2"/></svg>',
                                    'xl' => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 13 L4 6 L16 6 L19 13 Z" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linejoin="round"/><circle cx="5.5" cy="14.5" r="1.8" stroke="currentColor" stroke-width="1.3"/><circle cx="14.5" cy="14.5" r="1.8" stroke="currentColor" stroke-width="1.3"/><path d="M4 10 H16" stroke="currentColor" stroke-width="1" opacity="0.4"/></svg>',
                                ];
                            @endphp
                            @foreach($rideOptions as $index => $option)
                            <button
                                type="button"
                                class="ktx-opt{{ $index === 0 ? ' is-active' : '' }}"
                                data-ride-option
                                data-option-key="{{ $option['key'] }}"
                                data-option-name="{{ $option['name'] }}"
                            >
                                <span class="ktx-opt__icon">{!! $optSvgs[$option['key']] ?? $optSvgs['eco'] !!}</span>
                                <span class="ktx-opt__body">
                                    <span class="ktx-opt__name">{{ $option['name'] }}</span>
                                    <span class="ktx-opt__desc">{{ $option['description'] }} · {{ $option['base_label'] }}</span>
                                </span>
                                <span class="ktx-opt__check">✓</span>
                            </button>
                            @endforeach
                        </div>
                        <div id="heroSelectedFormula" style="display:none;">Eco</div>
                    </div>

                    {{-- Estimate (hidden until route is calculated) --}}
                    <div id="estimateSection" hidden style="margin-bottom:14px;">
                        <div class="ktx-estimate">
                            <div class="ktx-estimate__head">
                                <div>
                                    <div class="ktx-estimate__label">Estimation du trajet</div>
                                    <div id="selectedRideMeta" class="ktx-estimate__meta">Eco · estimation en cours</div>
                                </div>
                                <div id="estPrice" class="ktx-estimate__price">-- FCFA</div>
                            </div>
                            <div class="ktx-stats">
                                <div class="ktx-stat">
                                    <div class="ktx-stat__k">Distance</div>
                                    <div id="estDistance" class="ktx-stat__v">-- km</div>
                                </div>
                                <div class="ktx-stat">
                                    <div class="ktx-stat__k">Duree</div>
                                    <div id="estDuration" class="ktx-stat__v">-- min</div>
                                </div>
                                <div class="ktx-stat">
                                    <div class="ktx-stat__k">Base</div>
                                    <div id="estPriceMap" class="ktx-stat__v">-- FCFA</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Paiement --}}
                    <div style="margin-bottom:14px;">
                        <div class="ktx-field-label">Paiement</div>
                        <div class="ktx-chips">
                            <label class="kende-chip is-selected">
                                <input type="radio" name="payment_method" value="cash" checked>
                                Especes
                            </label>
                            <label class="kende-chip">
                                <input type="radio" name="payment_method" value="momo">
                                Mobile Money
                            </label>
                        </div>
                    </div>

                    {{-- CTA --}}
                    <button id="confirmBtn" type="button" class="ktx-confirm" disabled>
                        <span>Confirmer la course</span>
                        <span id="heroConfirmPrice" class="ktx-confirm__price">--</span>
                    </button>

                    {{-- Details collapsible --}}
                    <div style="margin-top:12px;">
                        <button type="button"
                                class="ktx-details-toggle"
                                aria-expanded="false"
                                onclick="this.setAttribute('aria-expanded', this.getAttribute('aria-expanded')==='true'?'false':'true'); this.nextElementSibling.classList.toggle('is-open')">
                            <span>Plus d'options (reperes, passagers, horaire)</span>
                            <span class="ktx-details-toggle__icon"><i class="fas fa-chevron-down"></i></span>
                        </button>
                        <div class="ktx-details-body">
                            <div class="ktx-grid2">
                                <div>
                                    <label class="ktx-field-label" for="pickupNote">Repere depart</label>
                                    <textarea id="pickupNote" name="pickup_note" class="ktx-textarea"
                                              placeholder="Immeuble, avenue, portail..."></textarea>
                                </div>
                                <div>
                                    <label class="ktx-field-label" for="dropoffNote">Repere arrivee</label>
                                    <textarea id="dropoffNote" name="dropoff_note" class="ktx-textarea"
                                              placeholder="Immeuble, avenue, portail..."></textarea>
                                </div>
                            </div>
                            <div class="ktx-grid2">
                                <div>
                                    <label class="ktx-field-label" for="passengerCount">Passagers</label>
                                    <select id="passengerCount" name="passenger_count" class="ktx-field-input">
                                        <option value="1">1 passager</option>
                                        <option value="2">2 passagers</option>
                                        <option value="3">3 passagers</option>
                                        <option value="4">4 passagers</option>
                                        <option value="5">5 passagers</option>
                                        <option value="6">6 passagers</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="ktx-field-label" for="scheduledAtInput">Heure programmee</label>
                                    <input id="scheduledAtInput" name="scheduled_at" class="ktx-field-input"
                                           type="datetime-local" disabled>
                                </div>
                            </div>
                            <div>
                                <div class="ktx-field-label">Moment du depart</div>
                                <div class="ktx-chips">
                                    <label class="kende-chip is-selected">
                                        <input type="radio" name="ride_timing" value="now" checked>
                                        Maintenant
                                    </label>
                                    <label class="kende-chip">
                                        <input type="radio" name="ride_timing" value="later">
                                        Programmer
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

        </aside>

        {{-- RIGHT — MAP --}}
        <div class="ktx-map-col">
            <div class="ktx-map-card">
                {{-- Top bar --}}
                <div class="ktx-map-topbar">
                    <div class="ktx-map-topbar__info">
                        <div class="ktx-map-topbar__title">Carte du trajet</div>
                        <div class="ktx-map-topbar__sub">Tracez le depart et l'arrivee sur la carte ou saisissez les adresses.</div>
                    </div>
                    <div id="geoState" class="ktx-geo-badge" data-state="idle">
                        <span class="ktx-geo-dot"></span>
                        <span>GPS en attente</span>
                    </div>
                </div>

                {{-- Map canvas --}}
                <div class="ktx-map-canvas">
                    <div id="txMapEmpty" class="kende-map__empty">
                        <div class="ktx-map-empty-card">
                            <div class="ktx-map-empty-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <div class="ktx-map-empty-title">Visualisez votre trajet</div>
                            <div class="ktx-map-empty-text">Ajoutez le depart et la destination pour afficher le trajet et l'estimation.</div>
                        </div>
                    </div>
                    <div id="taxiMap"></div>
                    <div id="txMapControls" class="kende-map__controls">
                        <button id="setPickupPinBtn" type="button" class="kende-map__ctrl is-active">Depart</button>
                        <button id="setDropoffPinBtn" type="button" class="kende-map__ctrl">Arrivee</button>
                        <button id="centerRouteBtn" type="button" class="kende-map__ctrl">Centrer</button>
                        <button id="clearRouteBtn" type="button" class="kende-map__ctrl">Effacer</button>
                    </div>
                </div>

                {{-- Summary strip --}}
                <div class="ktx-map-summary">
                    <div class="ktx-map-summary-cell">
                        <div class="ktx-map-summary-k">Depart</div>
                        <div id="summaryPickup" class="ktx-map-summary-v">Non defini</div>
                    </div>
                    <div class="ktx-map-summary-cell">
                        <div class="ktx-map-summary-k">Arrivee</div>
                        <div id="summaryDropoff" class="ktx-map-summary-v">A definir</div>
                    </div>
                    <div class="ktx-map-summary-cell">
                        <div class="ktx-map-summary-k">Distance</div>
                        <div id="estDistanceMap" class="ktx-map-summary-v">-- km</div>
                    </div>
                    <div class="ktx-map-summary-cell">
                        <div class="ktx-map-summary-k">Duree</div>
                        <div id="estDurationMap" class="ktx-map-summary-v">-- min</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @if($transportTestimonials->isNotEmpty())
    <div class="ktx-proof-grid">
        @foreach($transportTestimonials as $item)
        <article class="ktx-proof-card">
            @if(!empty($item['tag']))
            <div class="ktx-proof-card__tag">{{ $item['tag'] }}</div>
            @endif
            <div class="ktx-proof-card__quote">{{ $item['quote'] }}</div>
            <div class="ktx-proof-card__meta">{{ trim(($item['name'] ?? '') . ' · ' . ($item['loc'] ?? ''), ' ·') }}</div>
        </article>
        @endforeach
    </div>
    @endif

    @if($transportOpportunities->isNotEmpty())
    <section class="ktx-op-section">
        <div class="ktx-op-section__head">
            <div>
                <div class="ktx-op-section__eyebrow">{{ $homeContent['opportunities_tag'] ?? 'Opportunites transport' }}</div>
                <div class="ktx-op-section__title">{{ $homeContent['opportunities_title'] ?? 'Grandissez avec Kende' }}</div>
            </div>
            <div class="ktx-op-section__body">{{ $homeContent['opportunities_subtitle'] ?? 'Rejoignez Kende comme chauffeur, partenaire flotte ou relais operationnel.' }}</div>
        </div>
        <div class="ktx-op-grid">
            @foreach($transportOpportunities as $item)
            <article class="ktx-op-card">
                @if(!empty($item['image']))
                <div class="ktx-op-card__media">
                    <img src="{{ $item['image'] }}" alt="">
                </div>
                @endif
                <div class="ktx-op-card__title">{{ $item['title'] }}</div>
                <div class="ktx-op-card__body">{{ $item['body'] }}</div>
                <a href="{{ $item['url'] }}" class="ktx-op-card__cta">{{ $item['cta'] }}</a>
            </article>
            @endforeach
        </div>
    </section>
    @endif

    {{-- FOOTER --}}
    <footer class="ktx-footer">
        <div>Kende &copy; {{ date('Y') }} · Brazzaville · Congo</div>
        <div class="ktx-footer-links">
            <a href="{{ $taxiUrl }}">Taxi</a>
            <a href="{{ $carpoolUrl }}">Covoiturage</a>
            <a href="{{ $rentalUrl }}">Location</a>
            <a href="{{ $busUrl }}">Bus</a>
            <a href="{{ $faqUrl }}">Aide</a>
            <a href="{{ $privacyUrl }}">Confidentialite</a>
        </div>
    </footer>

</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    const TAXI_CONFIG = {
        pricing:         @json($pricingData),
        rideOptions:     @json($rideOptions),
        estimateUrl:     @json(route('transport.xhr.estimate')),
        geocodeUrl:      @json(route('transport.xhr.geocode')),
        reverseUrl:      @json(route('transport.xhr.reverse')),
        routeUrl:        @json(route('transport.xhr.route')),
        bookingUrl:      @json(route('transport.xhr.bookings.store')),
        isAuthenticated: @json(auth()->check()),
        loginUrl:        @json(route('user.login', ['redirect' => url()->current()])),
        defaultCity:     { lat: -4.2767, lng: 15.2832, label: 'Brazzaville, Republique du Congo' },
        csrf:            @json(csrf_token())
    };

    let map;
    let pickupMarker     = null;
    let dropoffMarker    = null;
    let routeLayer       = null;
    let activePinTarget  = 'pickup';
    let currentEstimate  = { distance: 0, duration: 0, basePrice: 0, finalPrice: 0 };
    let pickupSearchTimeout  = null;
    let dropoffSearchTimeout = null;
    let selectedAddressDetails = { pickup: null, dropoff: null };
    let pinConfirmationState = { pickup: false, dropoff: false };

    document.addEventListener('DOMContentLoaded', () => {
        initMap();
        bindSearchInput('pickup');
        bindSearchInput('dropoff');
        bindRideOptions();
        bindModeChips();
        bindMapControls();
        bindLocateMe();
        bindEstimateButton();
        bindConfirmButton();
    });

    function initMap() {
        const canvas = document.getElementById('taxiMap');
        if (!canvas) return;

        map = L.map('taxiMap', { zoomControl: true }).setView([TAXI_CONFIG.defaultCity.lat, TAXI_CONFIG.defaultCity.lng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        map.on('click', async (event) => {
            const details = await reverseGeocode({ lat: event.latlng.lat, lng: event.latlng.lng });
            applySelectedAddress(activePinTarget, details, { source: 'map' });
        });
    }

    function bindSearchInput(type) {
        const input = document.getElementById(type === 'pickup' ? 'pickupInput' : 'dropoffInput');
        if (!input) return;

        input.addEventListener('input', () => {
            const query = input.value.trim();
            updateSummary(type, query || (type === 'pickup' ? 'Non defini' : 'A definir'));
            clearTimeout(type === 'pickup' ? pickupSearchTimeout : dropoffSearchTimeout);

            if (query.length < 3) { hideSuggestions(type); return; }

            const timeoutId = setTimeout(async () => {
                const suggestions = await geocodeAddressList(query);
                renderSuggestions(type, suggestions);
            }, 280);

            if (type === 'pickup') pickupSearchTimeout = timeoutId;
            else dropoffSearchTimeout = timeoutId;
        });
    }

    function bindRideOptions() {
        document.querySelectorAll('[data-ride-option]').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('[data-ride-option]').forEach((item) => item.classList.remove('is-active'));
                button.classList.add('is-active');
                document.getElementById('selectedRideOption').value = button.dataset.optionKey;
                document.getElementById('heroSelectedFormula').textContent = button.dataset.optionName || 'Eco';
                if (currentEstimate.basePrice > 0) refreshEstimatePrice();
            });
        });
    }

    function bindModeChips() {
        document.querySelectorAll('.kende-chip').forEach((chip) => {
            const input = chip.querySelector('input');
            if (!input) return;
            input.addEventListener('change', () => {
                document.querySelectorAll(`input[name="${input.name}"]`).forEach((radio) => {
                    const label = radio.closest('.kende-chip');
                    if (label) label.classList.toggle('is-selected', radio.checked);
                });

                if (input.name === 'ride_timing') {
                    const scheduledAtInput = document.getElementById('scheduledAtInput');
                    const isLater = document.querySelector('input[name="ride_timing"]:checked')?.value === 'later';
                    scheduledAtInput.disabled = !isLater;
                    if (!isLater) scheduledAtInput.value = '';
                }
            });
        });
    }

    function bindMapControls() {
        document.getElementById('setPickupPinBtn')?.addEventListener('click', () => setActivePinTarget('pickup'));
        document.getElementById('setDropoffPinBtn')?.addEventListener('click', () => setActivePinTarget('dropoff'));
        document.getElementById('centerRouteBtn')?.addEventListener('click', centerRoute);
        document.getElementById('clearRouteBtn')?.addEventListener('click', clearRoute);
    }

    function bindLocateMe() {
        document.getElementById('locateMeBtn')?.addEventListener('click', () => requestCurrentPosition(true));
    }

    function bindEstimateButton() {
        document.getElementById('heroSearchBtn')?.addEventListener('click', async () => {
            const pickupInput  = document.getElementById('pickupInput');
            const dropoffInput = document.getElementById('dropoffInput');
            const pickupValue  = pickupInput.value.trim();
            const dropoffValue = dropoffInput.value.trim();

            if (!pickupValue)  { pickupInput.focus();  updatePickupStatus('Ajoutez un depart avant de continuer.', true);       return; }
            if (!dropoffValue) { dropoffInput.focus(); updateDropoffStatus('Ajoutez une destination avant de continuer.', true); return; }

            await ensureCoordinatesForInput('pickup',  pickupValue);
            await ensureCoordinatesForInput('dropoff', dropoffValue);

            if (!hasCoordinates('pickup') || !hasCoordinates('dropoff')) {
                updateDropoffStatus('Impossible de calculer le trajet. Precisez mieux les adresses.', true);
                return;
            }

            await calculateRoute();
        });
    }

    function bindConfirmButton() {
        document.getElementById('confirmBtn')?.addEventListener('click', async () => {
            if (!TAXI_CONFIG.isAuthenticated) { window.location.href = TAXI_CONFIG.loginUrl; return; }

            const pickupInput  = document.getElementById('pickupInput').value.trim();
            const dropoffInput = document.getElementById('dropoffInput').value.trim();
            const pickupLat    = document.getElementById('p_lat').value;
            const pickupLng    = document.getElementById('p_lng').value;
            const dropoffLat   = document.getElementById('d_lat').value;
            const dropoffLng   = document.getElementById('d_lng').value;
            const rideTiming   = document.querySelector('input[name="ride_timing"]:checked')?.value || 'now';
            const scheduledAt  = document.getElementById('scheduledAtInput').value;

            if (!pickupInput || !dropoffInput || !pickupLat || !pickupLng || !dropoffLat || !dropoffLng) {
                alert('Definissez le depart et la destination avant de confirmer.');
                return;
            }

            if (requiresPinConfirmation('pickup')) {
                updatePickupStatus('Confirmez precisement le depart sur la carte avant de reserver.', true);
                setActivePinTarget('pickup');
                alert('Confirmez precisement le depart sur la carte.');
                return;
            }

            if (requiresPinConfirmation('dropoff')) {
                updateDropoffStatus('Confirmez precisement la destination sur la carte avant de reserver.', true);
                setActivePinTarget('dropoff');
                alert('Confirmez precisement la destination sur la carte.');
                return;
            }

            if (rideTiming === 'later' && !scheduledAt) {
                alert('Choisissez une date et une heure pour une course programmee.');
                return;
            }

            const button = document.getElementById('confirmBtn');
            button.disabled = true;
            button.querySelector('span').textContent = 'Creation de la course...';

            const payload = {
                type:               'taxi',
                pickup_address:     formatAddressWithNote('pickupInput',  'pickupNote'),
                pickup_lat:         pickupLat,
                pickup_lng:         pickupLng,
                pickup_precision_level: selectedAddressDetails.pickup?.precision?.level || null,
                pickup_pin_confirmed: !!pinConfirmationState.pickup,
                pickup_accuracy_meters: selectedAddressDetails.pickup?.gpsAccuracyMeters || null,
                dropoff_address:    formatAddressWithNote('dropoffInput', 'dropoffNote'),
                dropoff_lat:        dropoffLat,
                dropoff_lng:        dropoffLng,
                dropoff_precision_level: selectedAddressDetails.dropoff?.precision?.level || null,
                dropoff_pin_confirmed: !!pinConfirmationState.dropoff,
                dropoff_accuracy_meters: selectedAddressDetails.dropoff?.gpsAccuracyMeters || null,
                estimated_distance: document.getElementById('estimatedDistance').value  || null,
                estimated_duration: document.getElementById('estimatedDuration').value  || null,
                estimated_price:    document.getElementById('estimatedPriceValue').value || null,
                total_price:        document.getElementById('estimatedPriceValue').value || null,
                scheduled_at:       rideTiming === 'later' ? scheduledAt : null,
                payment_method:     document.querySelector('input[name="payment_method"]:checked')?.value || 'cash',
                notes: JSON.stringify({
                    pickup_note:      document.getElementById('pickupNote').value.trim(),
                    dropoff_note:     document.getElementById('dropoffNote').value.trim(),
                    passenger_count:  document.getElementById('passengerCount').value,
                    ride_option:      document.getElementById('selectedRideOption').value || 'eco',
                    timing:           rideTiming
                })
            };

            try {
                const response = await fetch(TAXI_CONFIG.bookingUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TAXI_CONFIG.csrf },
                    body: JSON.stringify(payload)
                });

                const data = await readTaxiJson(response);

                if (response.ok && data?.booking?.uuid) {
                    window.location.href = `/transport/booking/${data.booking.uuid}`;
                    return;
                }

                if (data?.address_confirmation?.pickup_requires_pin_confirmation) {
                    updatePickupStatus('Confirmez precisement le depart sur la carte avant de reserver.', true);
                    setActivePinTarget('pickup');
                } else if (data?.address_confirmation?.dropoff_requires_pin_confirmation) {
                    updateDropoffStatus('Confirmez precisement la destination sur la carte avant de reserver.', true);
                    setActivePinTarget('dropoff');
                }

                alert(data?.message || 'Reservation impossible pour le moment.');
            } catch (error) {
                console.error(error);
                alert('Erreur reseau pendant la reservation.');
            }

            button.disabled = false;
            button.querySelector('span').textContent = 'Confirmer la course';
        });
    }

    function formatAddressWithNote(addressId, noteId) {
        const address = document.getElementById(addressId).value.trim();
        const note    = document.getElementById(noteId).value.trim();
        return note ? `${address} | Repere: ${note}` : address;
    }

    function setActivePinTarget(type) {
        activePinTarget = type;
        document.getElementById('setPickupPinBtn')?.classList.toggle('is-active', type === 'pickup');
        document.getElementById('setDropoffPinBtn')?.classList.toggle('is-active', type === 'dropoff');
    }

    function hasCoordinates(type) {
        const lat = document.getElementById(type === 'pickup' ? 'p_lat' : 'd_lat').value;
        const lng = document.getElementById(type === 'pickup' ? 'p_lng' : 'd_lng').value;
        return Boolean(lat && lng);
    }

    async function ensureCoordinatesForInput(type, query) {
        if (hasCoordinates(type)) return;
        const match = await geocodeAddress(query);
        if (match) applySelectedAddress(type, match);
    }

    async function geocodeAddress(query) {
        const items = await geocodeAddressList(query, 1);
        return items[0] || null;
    }

    async function readTaxiJson(response) {
        try {
            return await response.json();
        } catch (error) {
            return null;
        }
    }

    async function geocodeAddressList(query, limit = 5) {
        if (!query) return [];
        try {
            const response = await fetch(`${TAXI_CONFIG.geocodeUrl}?q=${encodeURIComponent(query)}&limit=${encodeURIComponent(limit)}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await readTaxiJson(response);
            if (!response.ok) return [];
            const items = (data.data || []).map((item) => buildAddressDetails({
                lat: parseFloat(item.lat),
                lng: parseFloat(item.lng),
                label: item.label,
                shortText: item.address_line || item.label,
                components: item.components || {},
                precision: item.precision || {},
                kind: item.kind || 'area',
                searchScore: item.search_score || null,
            }));

            if (items.length > 0) {
                return items;
            }

            const hints = (data.meta?.clarification_suggestions || []).map((item) => buildAddressDetails({
                lat: parseFloat(item.lat),
                lng: parseFloat(item.lng),
                label: item.label,
                shortText: item.address_line || item.label,
                components: item.components || {},
                precision: item.precision || {},
                kind: item.kind || 'district',
                searchScore: item.search_score || null,
                isClarificationHint: true,
            }));

            return hints;
        } catch (error) {
            console.error(error);
            return [];
        }
    }

    async function reverseGeocode(position) {
        try {
            const response = await fetch(`${TAXI_CONFIG.reverseUrl}?lat=${encodeURIComponent(position.lat)}&lng=${encodeURIComponent(position.lng)}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await readTaxiJson(response);
            if (response.ok) {
                if (data && data.data && data.data.label) {
                    return buildAddressDetails({
                        lat: position.lat,
                        lng: position.lng,
                        label: data.data.label,
                        shortText: data.data.address_line || data.data.label,
                        components: data.data.components || {},
                        precision: data.data.precision || {},
                        gpsAccuracyMeters: position.accuracy || null,
                    });
                }
            }
        } catch (error) {
            console.error(error);
        }
        return buildAddressDetails({
            lat: position.lat,
            lng: position.lng,
            label: `${position.lat.toFixed(6)}, ${position.lng.toFixed(6)}`,
            shortText: 'Position',
            precision: { source: 'gps', level: 'area', house_number_confirmed: false, road_confirmed: false, district_confirmed: false },
            gpsAccuracyMeters: position.accuracy || null,
        });
    }

    function buildAddressDetails({
        lat,
        lng,
        label,
        shortText,
        components = {},
        precision = {},
        gpsAccuracyMeters = null,
        kind = 'area',
        searchScore = null,
        isClarificationHint = false,
    }) {
        return {
            lat,
            lng,
            label,
            addressLine: shortText || label,
            components,
            precision,
            gpsAccuracyMeters: typeof gpsAccuracyMeters === 'number' ? gpsAccuracyMeters : null,
            kind,
            searchScore,
            isClarificationHint,
        };
    }

    function formatAddressPrecision(details) {
        const parts = [];
        if (details.gpsAccuracyMeters && Number.isFinite(details.gpsAccuracyMeters)) {
            parts.push(`GPS ±${Math.round(details.gpsAccuracyMeters)} m`);
        }

        const level = details.precision?.level || 'area';
        if (level === 'door') {
            parts.push('numero et rue identifies');
        } else if (level === 'street') {
            parts.push('rue detectee');
            if (!details.precision?.house_number_confirmed) parts.push('numero non confirme');
        } else if (level === 'district') {
            parts.push('quartier detecte');
        } else {
            parts.push('position approximative');
        }

        return parts.join(' · ');
    }

    function renderSuggestions(type, suggestions) {
        const box = document.getElementById(type === 'pickup' ? 'pickupSuggestions' : 'dropoffSuggestions');
        if (!box) return;
        box.innerHTML = '';
        if (!suggestions.length) { box.classList.remove('is-visible'); return; }
        suggestions.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'kende-suggestion';
            button.textContent = item.isClarificationHint ? `Suggestion locale · ${item.label}` : item.label;
            button.addEventListener('click', () => applySelectedAddress(type, item));
            box.appendChild(button);
        });
        box.classList.add('is-visible');
    }

    function hideSuggestions(type) {
        const box = document.getElementById(type === 'pickup' ? 'pickupSuggestions' : 'dropoffSuggestions');
        if (!box) return;
        box.classList.remove('is-visible');
    }

    function applySelectedAddress(type, item, options = {}) {
        const source = options.source || 'search';
        const input = document.getElementById(type === 'pickup' ? 'pickupInput' : 'dropoffInput');
        if (input) input.value = item.label || '';
        selectedAddressDetails[type] = item;
        pinConfirmationState[type] = source === 'map' || source === 'gps';

        if (type === 'pickup') {
            setMarker('pickup', item);
            updatePickupStatus(buildAddressStatusMessage(type, item));
            updateSummary('pickup', item.label || 'Depart defini');
            setActivePinTarget('dropoff');
        } else {
            setMarker('dropoff', item);
            updateDropoffStatus(buildAddressStatusMessage(type, item));
            updateSummary('dropoff', item.label || 'Arrivee definie');
        }

        hideSuggestions(type);
        toggleMapEmpty();
        refreshConfirmEligibility();

        if (pickupMarker && dropoffMarker) calculateRoute();
        else if (map) map.setView([item.lat, item.lng], 15);
    }

    function buildAddressStatusMessage(type, details) {
        const base = formatAddressPrecision(details);
        if (!requiresPinConfirmation(type)) return base;

        return `${base} · confirmez le point exact sur la carte`;
    }

    function requiresPinConfirmation(type) {
        const details = selectedAddressDetails[type];
        if (!details) return false;

        return ['district', 'area', 'blind'].includes(details.precision?.level || 'blind')
            && !pinConfirmationState[type];
    }

    function updatePickupStatus(message, isError = false) {
        const node = document.getElementById('pickupStatus');
        if (!node) return;
        node.textContent = message;
        node.style.color = isError ? 'var(--k-red)' : 'var(--k-t3)';
    }

    function updateDropoffStatus(message, isError = false) {
        const node = document.getElementById('dropoffStatus');
        if (!node) return;
        node.textContent = message;
        node.style.color = isError ? 'var(--k-red)' : 'var(--k-t3)';
    }

    function updateSummary(type, value) {
        if (type === 'pickup') document.getElementById('summaryPickup').textContent  = value || 'Non defini';
        else                   document.getElementById('summaryDropoff').textContent = value || 'A definir';
    }

    function updateGeoState(state, label) {
        const node = document.getElementById('geoState');
        if (!node) return;
        node.dataset.state = state;
        node.querySelector('span:last-child').textContent = label;
    }

    function requestCurrentPosition(showErrors = false) {
        if (!navigator.geolocation) {
            updateGeoState('error', 'GPS indisponible');
            updatePickupStatus('Votre appareil ne prend pas en charge la geolocalisation.', true);
            return;
        }
        updateGeoState('loading', 'Recherche GPS');
        navigator.geolocation.getCurrentPosition(async (position) => {
            const current = {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                accuracy: typeof position.coords.accuracy === 'number' ? position.coords.accuracy : null,
            };
            const details = await reverseGeocode(current);
            applySelectedAddress('pickup', details, { source: 'gps' });
            if (map) map.setView([current.lat, current.lng], 15);
            const geoLabel = current.accuracy ? `GPS ±${Math.round(current.accuracy)} m` : 'GPS actif';
            updateGeoState('ready', geoLabel);
        }, () => {
            updateGeoState('error', 'GPS refuse');
            updatePickupStatus('Impossible de recuperer votre position.', true);
            if (showErrors) alert('Autorisez la localisation dans votre navigateur ou saisissez le depart.');
        }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 });
    }

    function setMarker(type, position) {
        const marker = L.marker([position.lat, position.lng]);
        if (type === 'pickup') {
            if (pickupMarker) map.removeLayer(pickupMarker);
            pickupMarker = marker.addTo(map);
            document.getElementById('p_lat').value = position.lat;
            document.getElementById('p_lng').value = position.lng;
        } else {
            if (dropoffMarker) map.removeLayer(dropoffMarker);
            dropoffMarker = marker.addTo(map);
            document.getElementById('d_lat').value = position.lat;
            document.getElementById('d_lng').value = position.lng;
        }
    }

    function toggleMapEmpty() {
        const empty = document.getElementById('txMapEmpty');
        if (!empty) return;
        empty.style.display = pickupMarker && dropoffMarker ? 'none' : 'flex';
    }

    function centerRoute() {
        if (routeLayer) { map.fitBounds(routeLayer.getBounds(), { padding: [40, 40] }); return; }
        if (pickupMarker && dropoffMarker) {
            map.fitBounds(L.latLngBounds([pickupMarker.getLatLng(), dropoffMarker.getLatLng()]), { padding: [40, 40] });
        }
    }

    function clearRoute() {
        if (pickupMarker)  { map.removeLayer(pickupMarker);  pickupMarker  = null; }
        if (dropoffMarker) { map.removeLayer(dropoffMarker); dropoffMarker = null; }
        if (routeLayer)    { map.removeLayer(routeLayer);    routeLayer    = null; }

        ['pickupInput','dropoffInput','pickupNote','dropoffNote','p_lat','p_lng','d_lat','d_lng',
         'estimatedDistance','estimatedDuration','estimatedPriceValue'].forEach((id) => {
            const f = document.getElementById(id);
            if (f) f.value = '';
        });

        currentEstimate = { distance: 0, duration: 0, basePrice: 0, finalPrice: 0 };

        document.getElementById('summaryPickup').textContent       = 'Non defini';
        document.getElementById('summaryDropoff').textContent      = 'A definir';
        document.getElementById('heroSelectedFormula').textContent = 'Eco';
        document.getElementById('estDistance').textContent         = '-- km';
        document.getElementById('estDuration').textContent         = '-- min';
        document.getElementById('estDistanceMap').textContent      = '-- km';
        document.getElementById('estDurationMap').textContent      = '-- min';
        document.getElementById('estPrice').textContent            = '-- FCFA';
        document.getElementById('estPriceMap').textContent         = '-- FCFA';
        document.getElementById('selectedRideMeta').textContent    = 'Eco · estimation en cours';
        document.getElementById('estimateSection').hidden          = true;
        document.getElementById('confirmBtn').disabled             = true;
        document.getElementById('heroConfirmPrice').textContent    = '--';
        selectedAddressDetails = { pickup: null, dropoff: null };
        pinConfirmationState = { pickup: false, dropoff: false };
        updatePickupStatus('Saisissez votre point de depart ou utilisez votre position.');
        updateDropoffStatus('Definissez clairement votre destination.');
        toggleMapEmpty();
        if (map) map.setView([TAXI_CONFIG.defaultCity.lat, TAXI_CONFIG.defaultCity.lng], 13);
    }

    function haversineKm(lat1, lng1, lat2, lng2) {
        const R    = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a    = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)**2;
        return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
    }

    async function calculateRoute() {
        const pLat = parseFloat(document.getElementById('p_lat').value || '0');
        const pLng = parseFloat(document.getElementById('p_lng').value || '0');
        const dLat = parseFloat(document.getElementById('d_lat').value || '0');
        const dLng = parseFloat(document.getElementById('d_lng').value || '0');
        if (!pLat || !pLng || !dLat || !dLng) return;

        try {
            const response = await fetch(TAXI_CONFIG.routeUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TAXI_CONFIG.csrf },
                body: JSON.stringify({ type: 'taxi', pickup_lat: pLat, pickup_lng: pLng, dropoff_lat: dLat, dropoff_lng: dLng })
            });
            const data  = await readTaxiJson(response);

            if (response.ok) {
                const route = data.data;
                if (route && route.geometry && route.distance_km) {
                    if (routeLayer) map.removeLayer(routeLayer);
                    routeLayer = L.geoJSON(route.geometry, {
                        style: { color: '#FF6B00', weight: 6, opacity: .95 }
                    }).addTo(map);
                    map.fitBounds(routeLayer.getBounds(), { padding: [40, 40] });
                    await updateEstimate(route.distance_km || 0, route.duration_minutes || 0, route.estimated_price || null);
                    toggleMapEmpty();
                    refreshConfirmEligibility();
                    return;
                }
            }
        } catch (error) {
            console.error(error);
        }
        updateDropoffStatus('Impossible de calculer le trajet pour le moment.', true);
    }

    async function updateEstimate(distance, duration, serverEstimatedPrice = null) {
        if (serverEstimatedPrice !== null) {
            currentEstimate.distance  = Number(distance)             || 0;
            currentEstimate.duration  = Number(duration)             || 0;
            currentEstimate.basePrice = Number(serverEstimatedPrice) || 0;
        refreshEstimatePrice();
        document.getElementById('estimateSection').hidden  = false;
        refreshConfirmEligibility();
        return;
        }

        try {
            const response = await fetch(TAXI_CONFIG.estimateUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TAXI_CONFIG.csrf },
                body: JSON.stringify({ type: 'taxi', distance, duration })
            });
            const data = await readTaxiJson(response);
            if (!response.ok) {
                throw new Error(data?.message || 'Estimation indisponible');
            }
            currentEstimate.distance  = Number(distance)  || 0;
            currentEstimate.duration  = Number(duration)  || 0;
            currentEstimate.basePrice = Number(data.estimated_price || calculateBaseEstimate(distance, duration));
        } catch (error) {
            console.error(error);
            currentEstimate.distance  = Number(distance) || 0;
            currentEstimate.duration  = Number(duration) || 0;
            currentEstimate.basePrice = calculateBaseEstimate(distance, duration);
            updateDropoffStatus('Estimation locale affichee. Vous pouvez continuer.', false);
        }

        refreshEstimatePrice();
        document.getElementById('estimateSection').hidden  = false;
        refreshConfirmEligibility();
    }

    function refreshConfirmEligibility() {
        const button = document.getElementById('confirmBtn');
        if (!button) return;

        const hasEstimate = !document.getElementById('estimateSection').hidden;
        const ready = hasEstimate && hasCoordinates('pickup') && hasCoordinates('dropoff');
        button.disabled = !ready || requiresPinConfirmation('pickup') || requiresPinConfirmation('dropoff');
    }

    function calculateBaseEstimate(distance, duration) {
        const p  = TAXI_CONFIG.pricing || {};
        return Math.max(Math.round(
            (Number(p.base_fare||0) + distance*Number(p.price_per_km||0) + duration*Number(p.price_per_minute||0))
            * Number(p.surge_multiplier||1)
        ), 0);
    }

    function refreshEstimatePrice() {
        const selectedKey    = document.getElementById('selectedRideOption').value || 'eco';
        const selectedOption = TAXI_CONFIG.rideOptions.find((o) => o.key === selectedKey) || TAXI_CONFIG.rideOptions[0];
        const multiplier     = Number(selectedOption.multiplier || 1);
        const minimumFare    = Number(TAXI_CONFIG.pricing.minimum_fare || 0);
        const finalPrice     = Math.max(Math.round(currentEstimate.basePrice * multiplier), Math.round(minimumFare));

        currentEstimate.finalPrice = finalPrice;

        document.getElementById('estDistance').textContent      = `${currentEstimate.distance.toFixed(1)} km`;
        document.getElementById('estDuration').textContent      = `${Math.ceil(currentEstimate.duration)} min`;
        document.getElementById('estDistanceMap').textContent   = `${currentEstimate.distance.toFixed(1)} km`;
        document.getElementById('estDurationMap').textContent   = `${Math.ceil(currentEstimate.duration)} min`;
        document.getElementById('estPrice').textContent         = formatFcfa(finalPrice);
        document.getElementById('estPriceMap').textContent      = formatFcfa(currentEstimate.basePrice || minimumFare);
        document.getElementById('heroConfirmPrice').textContent = formatFcfa(finalPrice);
        document.getElementById('selectedRideMeta').textContent = `${selectedOption.name} · base ${formatFcfa(currentEstimate.basePrice || minimumFare)} · x${multiplier.toFixed(2)}`;
        document.getElementById('estimatedDistance').value      = currentEstimate.distance.toFixed(2);
        document.getElementById('estimatedDuration').value      = Math.ceil(currentEstimate.duration);
        document.getElementById('estimatedPriceValue').value    = finalPrice;
    }

    function formatFcfa(value) {
        return `${Math.round(Number(value||0)).toLocaleString('fr-FR')} FCFA`;
    }

    document.addEventListener('click', (event) => {
        if (!event.target.closest('#pickupSuggestions')  && !event.target.closest('#pickupInput'))  hideSuggestions('pickup');
        if (!event.target.closest('#dropoffSuggestions') && !event.target.closest('#dropoffInput')) hideSuggestions('dropoff');
    });
</script>
@endsection
