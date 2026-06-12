@extends('frontend.layouts.transport')
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
    $heroVisual = $resolveHomeMedia($homeContent['hero_transport_image'] ?? null);
    $heroStyle = $heroVisual
        ? "background-image:linear-gradient(135deg, rgba(255,255,255,.95) 0%, rgba(255,255,255,.86) 36%, rgba(255,255,255,.62) 100%), url('{$heroVisual}'); background-size:cover; background-position:center;"
        : null;
    $heroDescription = $homeContent['hero_description'] ?? "Kende met à votre disposition des chauffeurs vérifiés, un suivi en temps réel et une réservation claire en quelques secondes.";
    $supportTitle = $homeContent['support_title'] ?? 'Support Kende';
    $supportDescription = $homeContent['support_description'] ?? "Une équipe joignable en cas de problème avant, pendant ou après votre trajet.";
    $supportCta = $homeContent['support_cta_text'] ?? 'Contacter le support Kende';
@endphp
@section('title', 'Kende — Transport au Congo')
@section('description', $heroDescription)

@section('styles')
<style>
:root{
    --k-or:#FF6B00; --k-or-10:rgba(255,107,0,.10);
    --k-gr:#009B3A; --k-gr-10:rgba(0,155,58,.10);
    --k-ye:#FBCE07; --k-re:#DC241F;
    --k-bg:#F5F5F7; --k-border:#E4E4E6;
    --k-t1:#111113; --k-t2:#444447; --k-t3:#888890;
    --k-r-sm:8px; --k-r-md:14px; --k-r-lg:22px;
}

.kd-hero{
    background:#fff;
    border-bottom:1px solid var(--k-border);
    padding:72px 0 64px;
    text-align:center;
    position:relative;
    overflow:hidden;
}
.kd-hero__eyebrow{
    display:inline-flex;align-items:center;gap:8px;
    background:var(--k-or-10);border:1px solid rgba(255,107,0,.2);
    border-radius:999px;padding:5px 14px;
    font-size:.78rem;font-weight:700;color:var(--k-or);
    letter-spacing:.04em;text-transform:uppercase;
    margin-bottom:20px;
}
.kd-hero__eyebrow-dot{width:6px;height:6px;background:var(--k-or);border-radius:50%;}
.kd-hero h1{
    font-size:clamp(2.2rem,5vw,3.8rem);font-weight:900;
    color:var(--k-t1);letter-spacing:-.04em;line-height:1.05;
    margin:0 0 16px;
}
.kd-hero h1 span{color:var(--k-or);}
.kd-hero p{
    font-size:1.05rem;color:var(--k-t2);line-height:1.7;
    max-width:560px;margin:0 auto 32px;
}
.kd-hero__cta-row{display:flex;align-items:center;justify-content:center;gap:12px;flex-wrap:wrap;}
.kd-btn{
    display:inline-flex;align-items:center;justify-content:center;gap:8px;
    height:48px;padding:0 22px;border-radius:999px;
    font-weight:800;font-size:.92rem;text-decoration:none;border:none;cursor:pointer;
}
.kd-btn--primary{background:var(--k-or);color:#fff;box-shadow:0 8px 20px rgba(255,107,0,.22);}
.kd-btn--ghost{background:#fff;color:var(--k-t1);border:1.5px solid var(--k-border);}

/* Services grid */
.kd-services{background:var(--k-bg);padding:64px 0;}
.kd-section-label{
    font-size:.75rem;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;
    color:var(--k-t3);margin-bottom:8px;
}
.kd-section-title{
    font-size:clamp(1.6rem,3vw,2.4rem);font-weight:900;
    color:var(--k-t1);letter-spacing:-.03em;margin:0 0 40px;
}
.kd-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(260px,1fr));
    gap:16px;
}
.kd-card{
    background:#fff;border:1px solid var(--k-border);
    border-radius:var(--k-r-lg);padding:24px;
    display:flex;flex-direction:column;gap:14px;
    transition:box-shadow .18s,transform .18s;
    text-decoration:none;color:inherit;
}
.kd-card:hover{box-shadow:0 12px 36px rgba(0,0,0,.09);transform:translateY(-2px);}
.kd-card__icon{
    width:48px;height:48px;border-radius:var(--k-r-sm);
    display:flex;align-items:center;justify-content:center;
    font-size:1.4rem;flex-shrink:0;
}
.kd-card:nth-child(1) .kd-card__icon{background:var(--k-or-10);color:var(--k-or);}
.kd-card:nth-child(2) .kd-card__icon{background:var(--k-gr-10);color:var(--k-gr);}
.kd-card:nth-child(3) .kd-card__icon{background:rgba(6,182,212,.10);color:#0891b2;}
.kd-card:nth-child(4) .kd-card__icon{background:rgba(139,92,246,.10);color:#7c3aed;}
.kd-card__body{flex:1;}
.kd-card__name{font-size:1.05rem;font-weight:800;color:var(--k-t1);margin:0 0 6px;}
.kd-card__desc{font-size:.875rem;color:var(--k-t2);line-height:1.55;margin:0;}
.kd-card__footer{display:flex;align-items:center;justify-content:space-between;}
.kd-card__link{
    font-size:.85rem;font-weight:700;color:var(--k-or);
    display:inline-flex;align-items:center;gap:5px;
}
.kd-card__arrow{
    width:28px;height:28px;border-radius:50%;
    background:var(--k-bg);border:1px solid var(--k-border);
    display:flex;align-items:center;justify-content:center;
    color:var(--k-t3);font-size:.75rem;
}

/* Info strip */
.kd-strip{background:#fff;border-top:1px solid var(--k-border);border-bottom:1px solid var(--k-border);padding:40px 0;}
.kd-strip__grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:24px;}
.kd-strip__item{display:flex;align-items:flex-start;gap:12px;}
.kd-strip__icon{
    width:38px;height:38px;border-radius:var(--k-r-sm);flex-shrink:0;
    display:flex;align-items:center;justify-content:center;font-size:.95rem;
}
.kd-strip__item:nth-child(1) .kd-strip__icon{background:var(--k-or-10);color:var(--k-or);}
.kd-strip__item:nth-child(2) .kd-strip__icon{background:var(--k-gr-10);color:var(--k-gr);}
.kd-strip__item:nth-child(3) .kd-strip__icon{background:rgba(251,206,7,.15);color:#a16207;}
.kd-strip__item:nth-child(4) .kd-strip__icon{background:rgba(139,92,246,.10);color:#7c3aed;}
.kd-strip__title{font-size:.92rem;font-weight:700;color:var(--k-t1);margin:0 0 3px;}
.kd-strip__desc{font-size:.82rem;color:var(--k-t3);margin:0;line-height:1.4;}
.kd-support{background:#fff;padding:0 0 56px}
.kd-support__box{
    border:1px solid var(--k-border);
    border-radius:var(--k-r-lg);
    background:linear-gradient(180deg,#fff 0%,#fafafc 100%);
    box-shadow:0 18px 46px rgba(17,17,19,.06);
    padding:22px 24px;
    display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap;
}
.kd-support__eyebrow{font-size:.72rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--k-t3);margin-bottom:8px}
.kd-support__title{font-size:1.12rem;font-weight:900;color:var(--k-t1);margin:0 0 6px}
.kd-support__body{font-size:.92rem;color:var(--k-t2);line-height:1.7;max-width:760px;margin:0}

/* Congo flag strip */
.kd-flag{display:flex;height:4px;}
.kd-flag span{flex:1;}

@media(max-width:640px){
    .kd-hero{padding:56px 0 48px;}
    .kd-services{padding:48px 0;}
    .kd-support{padding-bottom:40px}
}
</style>
@endsection

@section('content')

{{-- Congo flag top strip --}}
<div class="kd-flag">
    <span style="background:#009B3A;"></span>
    <span style="background:#FBCE07;"></span>
    <span style="background:#DC241F;"></span>
</div>

{{-- Hero --}}
<section class="kd-hero{{ $heroVisual ? ' kd-hero--with-media' : '' }}" @if($heroStyle) style="{{ $heroStyle }}" @endif>
    <div class="container">
        <div class="kd-hero__eyebrow">
            <span class="kd-hero__eyebrow-dot"></span>
            Brazzaville · Pointe-Noire · Congo
        </div>
        <h1>Vos déplacements,<br><span>simples et fiables.</span></h1>
        <p>{{ $heroDescription }}</p>
        <div class="kd-hero__cta-row">
            <a href="{{ route('transport.taxi') }}" class="kd-btn kd-btn--primary">
                <i class="fas fa-taxi"></i> Réserver un taxi
            </a>
            @auth
            <a href="{{ route('transport.my_bookings') }}" class="kd-btn kd-btn--ghost">
                <i class="fas fa-list-ul"></i> Mes réservations
            </a>
            @endauth
        </div>
    </div>
</section>

<section class="kd-support">
    <div class="container">
        <div class="kd-support__box">
            <div>
                <div class="kd-support__eyebrow">Assistance</div>
                <h2 class="kd-support__title">{{ $supportTitle }}</h2>
                <p class="kd-support__body">{{ $supportDescription }}</p>
            </div>
            <a href="{{ route('contact.us', ['brand' => 'kende']) }}" class="kd-btn kd-btn--primary">{{ $supportCta }}</a>
        </div>
    </div>
</section>

{{-- Services --}}
<section class="kd-services">
    <div class="container">
        <p class="kd-section-label">Services</p>
        <h2 class="kd-section-title">Choisissez votre service</h2>
        <div class="kd-grid">
            <a href="{{ route('transport.taxi') }}" class="kd-card">
                <div class="kd-card__icon"><i class="fas fa-taxi"></i></div>
                <div class="kd-card__body">
                    <h3 class="kd-card__name">Taxi</h3>
                    <p class="kd-card__desc">Course urbaine avec chauffeur dédié, point de départ, destination et confirmation avant validation.</p>
                </div>
                <div class="kd-card__footer">
                    <span class="kd-card__link">Réserver <i class="fas fa-arrow-right" style="font-size:.7rem;"></i></span>
                    <span class="kd-card__arrow"><i class="fas fa-chevron-right"></i></span>
                </div>
            </a>

            <a href="{{ route('transport.carpool') }}" class="kd-card">
                <div class="kd-card__icon"><i class="fas fa-users"></i></div>
                <div class="kd-card__body">
                    <h3 class="kd-card__name">Covoiturage</h3>
                    <p class="kd-card__desc">Partagez un trajet avec d'autres passagers. Une option souple et économique pour vos déplacements.</p>
                </div>
                <div class="kd-card__footer">
                    <span class="kd-card__link">Voir les trajets <i class="fas fa-arrow-right" style="font-size:.7rem;"></i></span>
                    <span class="kd-card__arrow"><i class="fas fa-chevron-right"></i></span>
                </div>
            </a>

            <a href="{{ route('transport.rental') }}" class="kd-card">
                <div class="kd-card__icon"><i class="fas fa-car-side"></i></div>
                <div class="kd-card__body">
                    <h3 class="kd-card__name">Location</h3>
                    <p class="kd-card__desc">Louez le véhicule idéal pour vos besoins, à la journée ou sur plusieurs jours avec ou sans chauffeur.</p>
                </div>
                <div class="kd-card__footer">
                    <span class="kd-card__link">Voir les véhicules <i class="fas fa-arrow-right" style="font-size:.7rem;"></i></span>
                    <span class="kd-card__arrow"><i class="fas fa-chevron-right"></i></span>
                </div>
            </a>

            <a href="{{ route('transport.bus') }}" class="kd-card">
                <div class="kd-card__icon"><i class="fas fa-bus"></i></div>
                <div class="kd-card__body">
                    <h3 class="kd-card__name">Bus interurbain</h3>
                    <p class="kd-card__desc">Réservez une place sur les lignes Brazzaville–Pointe-Noire et autres destinations planifiées.</p>
                </div>
                <div class="kd-card__footer">
                    <span class="kd-card__link">Voir les lignes <i class="fas fa-arrow-right" style="font-size:.7rem;"></i></span>
                    <span class="kd-card__arrow"><i class="fas fa-chevron-right"></i></span>
                </div>
            </a>
        </div>
    </div>
</section>

{{-- Info strip --}}
<section class="kd-strip">
    <div class="container">
        <div class="kd-strip__grid">
            <div class="kd-strip__item">
                <div class="kd-strip__icon"><i class="fas fa-shield-alt"></i></div>
                <div>
                    <p class="kd-strip__title">Chauffeurs vérifiés</p>
                    <p class="kd-strip__desc">Chaque chauffeur est identifié et validé avant d'accéder à la plateforme.</p>
                </div>
            </div>
            <div class="kd-strip__item">
                <div class="kd-strip__icon"><i class="fas fa-map-marker-alt"></i></div>
                <div>
                    <p class="kd-strip__title">Suivi en temps réel</p>
                    <p class="kd-strip__desc">Suivez votre chauffeur sur la carte depuis la prise en charge jusqu'à destination.</p>
                </div>
            </div>
            <div class="kd-strip__item">
                <div class="kd-strip__icon"><i class="fas fa-mobile-alt"></i></div>
                <div>
                    <p class="kd-strip__title">Paiement flexible</p>
                    <p class="kd-strip__desc">Espèces ou Mobile Money — choisissez votre mode de paiement préféré.</p>
                </div>
            </div>
            <div class="kd-strip__item">
                <div class="kd-strip__icon"><i class="fas fa-headset"></i></div>
                <div>
                    <p class="kd-strip__title">Support disponible</p>
                    <p class="kd-strip__desc">Une équipe joignable en cas de problème avant, pendant ou après votre trajet.</p>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
