@extends('frontend.layouts.colis')
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
    $colisHeroBadge = 'Mema';
    $colisHeroDescription = $homeContent['hero_description'] ?? 'Accedez rapidement au suivi, a l expedition, au support et aux conditions utiles. Chaque colis est trace de la prise en charge jusqu a la remise confirmee.';
    $colisSupportTitle = $homeContent['support_title'] ?? 'Besoin d aide sur un colis en cours ?';
    $colisSupportDescription = $homeContent['support_description'] ?? 'Contactez le support ou ouvrez une reclamation en quelques instants. Notre equipe prend en charge votre demande jusqu a resolution.';
    $colisSupportCta = $homeContent['support_cta_text'] ?? 'Contacter le support';
    $colisTestimonials = collect([1, 2, 3])->map(function ($index) use ($homeContent) {
        return [
            'tag' => $homeContent['testimonial_' . $index . '_tag'] ?? null,
            'quote' => $homeContent['testimonial_' . $index . '_quote'] ?? null,
            'name' => $homeContent['testimonial_' . $index . '_name'] ?? null,
            'loc' => $homeContent['testimonial_' . $index . '_loc'] ?? null,
        ];
    })->filter(fn ($item) => filled($item['quote']))->values();
    $colisOpportunities = collect([1, 2, 3])->map(function ($index) use ($homeContent, $resolveHomeMedia) {
        return [
            'title' => $homeContent['opportunity_' . $index . '_title'] ?? null,
            'body' => $homeContent['opportunity_' . $index . '_body'] ?? null,
            'cta' => $homeContent['opportunity_' . $index . '_cta'] ?? null,
            'url' => $homeContent['opportunity_' . $index . '_url'] ?? null,
            'image' => $resolveHomeMedia($homeContent['opportunity_' . $index . '_image'] ?? null),
        ];
    })->filter(fn ($item) => filled($item['title']) && filled($item['body']) && filled($item['url']))->values();
@endphp

@section('title', 'Mema — Livraison de colis au Congo')
@section('description', $colisHeroDescription)

@php
    $siteContext = $siteContext ?? app(\App\Services\SiteContextService::class)->bootstrap(request());
    $currentLocale = data_get($siteContext, 'locale', app()->getLocale());
    $supportedLocales = array_keys(data_get($siteContext, 'supported_locales', ['fr' => 'Français']));
    $alternateLocale = collect($supportedLocales)->first(fn ($locale) => $locale !== $currentLocale) ?: $currentLocale;
    $localeSwitcherUrl = route('site.locale.switch', ['locale' => $alternateLocale, 'redirect' => url()->full()]);
    $claimUrl = route('contact.us', ['brand' => 'mema', 'topic' => 'reclamation-colis']);
    $heroVisual = $resolveHomeMedia($homeContent['hero_colis_image'] ?? null)
        ?: (file_exists(public_path('images/ai/colis-hero-pro.png'))
            ? asset('images/ai/colis-hero-pro.png')
            : asset('images/i2.jpg'));
    $ctaVisual = file_exists(public_path('images/ai/colis-cta-pro.png'))
        ? asset('images/ai/colis-cta-pro.png')
        : asset('images/driver_images/image_picker1484071899304326756-5f28730474536.jpg');
    $newsUrl = url('/news');
    $faqUrl = route('faq');
    $offersUrl = route('offers');
    $helpUrl = route('help', ['brand' => 'mema']);
    $contactUrl = route('contact.us', ['brand' => 'mema']);
    $aboutUrl = route('about.us', ['brand' => 'mema']);
    $driverUrl = route('driver');
    $partnerUrl = route('partner');
    $legalUrl = route('legal.notices', ['brand' => 'mema']);
    $cookiesUrl = route('cookies.policy', ['brand' => 'mema']);
    $privacyUrl = route('privacy.policy', ['brand' => 'mema']);
    $refundUrl = route('refund.policy');
    $dataDeletionUrl = route('data.deletion', ['brand' => 'mema']);
    $siteMapUrl = route('site.map');
    $colisCreateUrl = route('colis.create');
    $colisLandingUrl = route('colis.landing');
@endphp

@section('content')
<div id="bd-colis-cursor">
    <div id="bd-colis-cursor-ring"></div>
    <div id="bd-colis-cursor-dot"></div>
</div>

<a class="bd-colis-wa-float" href="{{ $helpUrl }}" aria-label="Contacter le support Mema">
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
    </svg>
</a>

<div class="bd-colis-page">
    <div class="bd-colis-ticker">
        <div class="bd-colis-ticker__track">
            <span class="bd-colis-ticker__item"><span class="bd-colis-ticker__gem"></span>Mema — Brazzaville, Pointe-Noire et zones principales</span>
            <span class="bd-colis-ticker__item"><span class="bd-colis-ticker__gem"></span>Suivi GPS en temps réel · Preuve de remise activée</span>
            <span class="bd-colis-ticker__item"><span class="bd-colis-ticker__gem"></span>Réclamation disponible en cas d'incident de remise</span>
            <span class="bd-colis-ticker__item"><span class="bd-colis-ticker__gem"></span>Parrainage — Recommandez Mema et bénéficiez d'avantages</span>
            <span class="bd-colis-ticker__item"><span class="bd-colis-ticker__gem"></span>Mema — Brazzaville, Pointe-Noire et zones principales</span>
            <span class="bd-colis-ticker__item"><span class="bd-colis-ticker__gem"></span>Suivi GPS en temps réel · Preuve de remise activée</span>
            <span class="bd-colis-ticker__item"><span class="bd-colis-ticker__gem"></span>Réclamation disponible en cas d'incident de remise</span>
            <span class="bd-colis-ticker__item"><span class="bd-colis-ticker__gem"></span>Parrainage — Recommandez Mema et bénéficiez d'avantages</span>
        </div>
    </div>

    <nav class="bd-colis-nav" id="bdColisNav">
        <a class="bd-colis-nav__logo" href="{{ $colisLandingUrl }}">
            <span class="bd-colis-nav__mark"></span>Mema
        </a>
        <div class="bd-colis-nav__links">
            <a class="is-active" href="{{ $colisLandingUrl }}">Mema</a>
            <a href="{{ $colisCreateUrl }}">Expedier</a>
            <a href="#bdColisCoverage">Suivi</a>
            <a href="{{ $helpUrl }}">Aide</a>
        </div>
        <div class="bd-colis-nav__end">
            <a class="bd-colis-nav__lang" href="{{ $localeSwitcherUrl }}">{{ strtoupper($currentLocale) }} / {{ strtoupper($alternateLocale) }}</a>
            <a class="bd-colis-nav__track" href="{{ route('colis.track_public') }}">Suivre</a>
            <a class="bd-colis-nav__cta" href="{{ $colisCreateUrl }}">Expedier</a>
        </div>
    </nav>

    <section class="bd-colis-hero" id="hero" style="background-image:url('{{ $heroVisual }}');">
        <div class="bd-colis-hero__overlay"></div>
        <canvas id="bdColisHeroCanvas"></canvas>
        <div class="bd-colis-hero__grid"></div>
        <div class="bd-colis-hero__geo"></div>
        <div class="bd-colis-hero__inner">
            <div class="bd-colis-hero__copy">
                <div class="bd-colis-breadcrumb bd-colis-reveal bd-colis-reveal-1">
                    <a href="{{ url('/') }}">Accueil</a>
                    <span class="bd-colis-breadcrumb__sep">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </span>
                    <span>Mema</span>
                </div>
                <div class="bd-colis-tag bd-colis-reveal bd-colis-reveal-1">{{ $colisHeroBadge }}</div>
                <h1 class="bd-colis-hero__title bd-colis-reveal bd-colis-reveal-2">Suivre, expedier<br><em>ou reclamer un colis.</em></h1>
                <p class="bd-colis-hero__subtitle bd-colis-reveal bd-colis-reveal-3">
                    {{ $colisHeroDescription }}
                </p>

                <form id="bdColisTrackForm" action="{{ route('colis.track_public') }}" method="GET" class="bd-colis-track-bar bd-colis-reveal bd-colis-reveal-4">
                    <input id="bdColisTrackInput" class="bd-colis-track-bar__input" type="text" name="tracking_number" placeholder="Numero de colis - ex. BD-2026-4821" />
                    <button class="bd-colis-track-bar__button" type="submit">Suivre</button>
                </form>

                <div class="bd-colis-quick-actions bd-colis-reveal bd-colis-reveal-5">
                    <a class="bd-colis-quick-actions__btn is-primary" href="{{ $colisCreateUrl }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        Expedier un colis
                    </a>
                    <a class="bd-colis-quick-actions__btn" href="{{ $claimUrl }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Faire une reclamation
                    </a>
                    <a class="bd-colis-quick-actions__btn" href="{{ $contactUrl }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Contacter le support
                    </a>
                </div>

                <div id="bdColisTrackResult" class="bd-colis-track-result bd-colis-reveal bd-colis-reveal-6" aria-live="polite"></div>
            </div>

            <div class="bd-colis-hero__visual bd-colis-reveal bd-colis-reveal-6">
                <div class="bd-colis-track-panel">
                    <div class="bd-colis-track-panel__header">
                        <div class="bd-colis-track-panel__title">Suivi de colis</div>
                        <div class="bd-colis-track-panel__id">BD-2026-4821</div>
                    </div>
                    <div class="bd-colis-track-panel__body">
                        <div class="bd-colis-track-panel__steps">
                            <div class="bd-colis-track-panel__step">
                                <div class="bd-colis-track-panel__left">
                                    <div class="bd-colis-track-panel__dot is-done"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
                                    <div class="bd-colis-track-panel__line is-done"></div>
                                </div>
                                <div class="bd-colis-track-panel__content">
                                    <div class="bd-colis-track-panel__label">Prise en charge</div>
                                    <div class="bd-colis-track-panel__time">Aujourd'hui, 09h14 — Poto-Poto, Brazzaville</div>
                                </div>
                            </div>
                            <div class="bd-colis-track-panel__step">
                                <div class="bd-colis-track-panel__left">
                                    <div class="bd-colis-track-panel__dot is-done"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
                                    <div class="bd-colis-track-panel__line is-done"></div>
                                </div>
                                <div class="bd-colis-track-panel__content">
                                    <div class="bd-colis-track-panel__label">Depart livreur</div>
                                    <div class="bd-colis-track-panel__time">Aujourd'hui, 09h31 — Livreur assigne</div>
                                </div>
                            </div>
                            <div class="bd-colis-track-panel__step">
                                <div class="bd-colis-track-panel__left">
                                    <div class="bd-colis-track-panel__dot is-active"><div class="bd-colis-track-panel__dot-core"></div></div>
                                    <div class="bd-colis-track-panel__line is-pending"></div>
                                </div>
                                <div class="bd-colis-track-panel__content">
                                    <div class="bd-colis-track-panel__label is-active">En route</div>
                                    <div class="bd-colis-track-panel__time">Depuis 09h31 — Estimation remise : 10h15</div>
                                    <div class="bd-colis-track-panel__note">Le livreur est en deplacement vers la destination.</div>
                                </div>
                            </div>
                            <div class="bd-colis-track-panel__step">
                                <div class="bd-colis-track-panel__left">
                                    <div class="bd-colis-track-panel__dot is-next"><div class="bd-colis-track-panel__dot-core is-next"></div></div>
                                </div>
                                <div class="bd-colis-track-panel__content">
                                    <div class="bd-colis-track-panel__label is-muted">Remis au destinataire</div>
                                    <div class="bd-colis-track-panel__time">En attente</div>
                                </div>
                            </div>
                        </div>
                        <div class="bd-colis-track-panel__divider"></div>
                        <div class="bd-colis-track-panel__meta">
                            <div><span>Expediteur</span><strong>Bacongo</strong></div>
                            <div><span>Destination</span><strong>Moungali</strong></div>
                            <div><span>Preuve</span><strong class="is-green">Activee</strong></div>
                        </div>
                    </div>
                </div>

                <div class="bd-colis-floating-card bd-colis-floating-card--proof">
                    <div class="bd-colis-floating-card__icon is-green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div>
                        <div class="bd-colis-floating-card__label">Preuve de remise</div>
                        <div class="bd-colis-floating-card__sub">Photo + confirmation</div>
                    </div>
                </div>
                <div class="bd-colis-floating-card bd-colis-floating-card--gps">
                    <div class="bd-colis-floating-card__icon is-gold">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div>
                        <div class="bd-colis-floating-card__label">GPS actif</div>
                        <div class="bd-colis-floating-card__sub">Suivi en direct</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bd-colis-scroll-indicator">
            <div class="bd-colis-scroll-indicator__line"></div>
            <div class="bd-colis-scroll-indicator__txt">Defiler</div>
        </div>
    </section>

    <section class="bd-colis-actions">
        <div class="bd-colis-wrap">
            <div class="bd-colis-actions__grid">
                <a class="bd-colis-actions__cell" href="{{ $colisLandingUrl }}#bdColisTrackForm">
                    <div class="bd-colis-actions__num">01</div>
                    <div class="bd-colis-actions__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </div>
                    <div class="bd-colis-actions__title">Suivre un colis</div>
                    <div class="bd-colis-actions__desc">Entrez votre numero de reference et consultez l'etat du colis en temps reel.</div>
                    <div class="bd-colis-actions__link">Acceder au suivi <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
                </a>
                <a class="bd-colis-actions__cell" href="{{ $colisCreateUrl }}">
                    <div class="bd-colis-actions__num">02</div>
                    <div class="bd-colis-actions__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    </div>
                    <div class="bd-colis-actions__title">Expedier un colis</div>
                    <div class="bd-colis-actions__desc">Creez un envoi avec les informations essentielles et validez rapidement.</div>
                    <div class="bd-colis-actions__link">Creer un envoi <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
                </a>
                <a class="bd-colis-actions__cell" href="{{ $claimUrl }}">
                    <div class="bd-colis-actions__num">03</div>
                    <div class="bd-colis-actions__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <div class="bd-colis-actions__title">Faire une reclamation</div>
                    <div class="bd-colis-actions__desc">Signalez un incident de remise ou une anomalie de destination.</div>
                    <div class="bd-colis-actions__link">Ouvrir une reclamation <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
                </a>
                <a class="bd-colis-actions__cell" href="{{ $contactUrl }}">
                    <div class="bd-colis-actions__num">04</div>
                    <div class="bd-colis-actions__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <div class="bd-colis-actions__title">Contacter le support</div>
                    <div class="bd-colis-actions__desc">Joignez l'equipe Mema pour une verification rapide.</div>
                    <div class="bd-colis-actions__link">Contacter l'equipe <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
                </a>
            </div>
        </div>
    </section>

    <section class="bd-colis-trust" id="bdColisTrust">
        <div class="bd-colis-wrap">
            <div class="bd-colis-trust__grid">
                <div class="bd-colis-trust__text">
                    <div class="bd-colis-tag">Suivi et preuve de remise</div>
                    <h2>{{ $homeContent['testimonials_title'] ?? 'Suivi <em>plus clair</em>,<br>preuve confirmee.' }}</h2>
                    <p>{{ $homeContent['testimonials_subtitle'] ?? "Chaque colis est trace depuis la prise en charge jusqu'a la remise au destinataire. La preuve de remise, photo et confirmation, reste lisible et exploitable." }}</p>
                    <div class="bd-colis-trust__points">
                        <div class="bd-colis-trust__point"><div class="bd-colis-trust__check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>Suivi plus clair depuis la prise en charge jusqu'a la remise</div>
                        <div class="bd-colis-trust__point"><div class="bd-colis-trust__check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>Preuve de remise generee automatiquement — photo et confirmation</div>
                        <div class="bd-colis-trust__point"><div class="bd-colis-trust__check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>Vision lisible du colis en cours, statut, localisation et estimation</div>
                    </div>
                    <div class="bd-colis-trust__buttons">
                        <a class="bd-colis-btn-primary" href="{{ route('colis.track_public') }}">Acceder au suivi <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                        <a class="bd-colis-btn-outline" href="{{ $colisCreateUrl }}">Expedier un colis</a>
                    </div>
                    @if($colisTestimonials->isNotEmpty())
                    <div class="bd-colis-testimonials">
                        @foreach($colisTestimonials as $item)
                        <article class="bd-colis-testimonial">
                            @if(!empty($item['tag']))
                            <div class="bd-colis-testimonial__tag">{{ $item['tag'] }}</div>
                            @endif
                            <div class="bd-colis-testimonial__quote">{{ $item['quote'] }}</div>
                            <div class="bd-colis-testimonial__meta">{{ trim(($item['name'] ?? '') . ' · ' . ($item['loc'] ?? ''), ' ·') }}</div>
                        </article>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div class="bd-colis-proof-visual">
                    <div class="bd-colis-proof-screen">
                        <div class="bd-colis-proof-screen__header">
                            <div class="bd-colis-proof-screen__title">Statut — BD-2026-4821</div>
                            <div class="bd-colis-proof-screen__live"><span></span>En direct</div>
                        </div>
                        <div class="bd-colis-proof-screen__body">
                            <div class="bd-colis-progress-track">
                                <div class="bd-colis-progress-track__step"><div class="bd-colis-progress-track__node is-done"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div><div class="bd-colis-progress-track__bar is-done"></div></div>
                                <div class="bd-colis-progress-track__step"><div class="bd-colis-progress-track__node is-done"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div><div class="bd-colis-progress-track__bar is-pending"></div></div>
                                <div class="bd-colis-progress-track__step"><div class="bd-colis-progress-track__node is-active"><div class="bd-colis-progress-track__core"></div></div><div class="bd-colis-progress-track__bar is-pending"></div></div>
                                <div class="bd-colis-progress-track__step"><div class="bd-colis-progress-track__node is-pending"><div class="bd-colis-progress-track__core is-pending"></div></div></div>
                            </div>
                            <div class="bd-colis-progress-labels">
                                <span class="is-done">Prise en charge</span>
                                <span class="is-done">Depart</span>
                                <span class="is-active">En route</span>
                                <span>Remis</span>
                            </div>
                            <div class="bd-colis-proof-screen__divider"></div>
                            <div class="bd-colis-proof-row">
                                <div class="bd-colis-proof-row__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                                <div>
                                    <div class="bd-colis-proof-row__label">Preuve de remise</div>
                                    <div class="bd-colis-proof-row__sub">Photo + signature activees</div>
                                </div>
                                <div class="bd-colis-proof-row__badge">Configuree</div>
                            </div>
                            <div class="bd-colis-proof-screen__divider"></div>
                            <div class="bd-colis-scan-container">
                                <div class="bd-colis-scan-container__line"></div>
                                <div class="bd-colis-scan-container__content">
                                    <div>
                                        <div class="bd-colis-barcode">
                                            <div class="b h32"></div><div class="b w h36"></div><div class="b h28"></div><div class="b h38"></div><div class="b w h30"></div><div class="b h36"></div><div class="b h26"></div><div class="b w h34"></div><div class="b h38"></div><div class="b h28"></div><div class="b w h32"></div><div class="b h36"></div><div class="b h30"></div><div class="b w h38"></div><div class="b h26"></div>
                                        </div>
                                        <div class="bd-colis-scan-container__number">BD-2026-4821-CG</div>
                                    </div>
                                    <div class="bd-colis-scan-container__meta">
                                        <div>Scan remise</div>
                                        <strong>En attente</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bd-colis-proof-floating bd-colis-proof-floating--left">
                        <div class="bd-colis-proof-floating__icon is-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg></div>
                        <div><div class="bd-colis-proof-floating__label">Taux de remise</div><div class="bd-colis-proof-floating__sub">94,2% confirmees</div></div>
                    </div>
                    <div class="bd-colis-proof-floating bd-colis-proof-floating--right">
                        <div class="bd-colis-proof-floating__icon is-gold"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
                        <div><div class="bd-colis-proof-floating__label">GPS · Livreur actif</div><div class="bd-colis-proof-floating__sub">Position mise a jour</div></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bd-colis-coverage" id="bdColisCoverage">
        <div class="bd-colis-wrap">
            <div class="bd-colis-tag bd-colis-tag--light">Zones desservies</div>
            <h2>Couverture <em>du service</em></h2>
            <p>Brazzaville, Pointe-Noire et zones principales selon disponibilite. La couverture est en expansion continue sur l'ensemble du territoire congolais.</p>
            <div class="bd-colis-coverage__tabs">
                <button class="bd-colis-coverage__tab is-active" data-tab="0">Brazzaville</button>
                <button class="bd-colis-coverage__tab" data-tab="1">Pointe-Noire</button>
                <button class="bd-colis-coverage__tab" data-tab="2">Zones principales</button>
            </div>
            <div class="bd-colis-coverage__panel is-active" data-panel="0">
                <div class="bd-colis-coverage__info">
                    <div class="bd-colis-badge is-active">Service actif</div>
                    <div class="bd-colis-coverage__city">Brazzaville</div>
                    <p>Envois quotidiens, remises locales rapides, courses urbaines inter-quartiers. Service operationnel dans les arrondissements principaux.</p>
                    <div class="bd-colis-coverage__items">
                        <div><span></span>Courses locales et remises rapides — Poto-Poto, Bacongo, Moungali, Makelekele, Talangai, Ouenze</div>
                        <div><span></span>Prise en charge organisee selon la zone de depart</div>
                        <div><span></span>Suivi GPS direct jusqu'a la remise avec preuve confirmee</div>
                    </div>
                </div>
                <div class="bd-colis-coverage__map">
                    <div class="bd-colis-coverage__map-head"><strong>Zone couverte</strong><span><i></i>Brazzaville</span></div>
                    <div class="bd-colis-coverage__map-body">
                        <svg class="bd-colis-congo-svg" viewBox="0 0 320 200" fill="none">
                            <path d="M60 30 Q80 20 110 25 Q140 18 170 30 Q200 25 230 40 Q250 55 255 80 Q265 110 250 140 Q240 165 220 175 Q195 188 165 185 Q135 188 110 178 Q85 170 70 155 Q50 135 45 108 Q38 80 45 58 Q50 40 60 30Z" fill="rgba(45,186,110,.06)" stroke="rgba(45,186,110,.2)" stroke-width="1.5"/>
                            <path d="M45 108 Q80 120 120 115 Q160 108 200 118 Q230 125 255 140" stroke="rgba(61,130,220,.3)" stroke-width="2" stroke-dasharray="4 3"/>
                            <circle cx="120" cy="115" r="16" fill="rgba(45,186,110,.15)" stroke="rgba(45,186,110,.4)"/>
                            <circle class="bd-colis-zone-ping" cx="120" cy="115" r="8" fill="#2dbb6e"/>
                            <text x="120" y="142">BRAZZAVILLE</text>
                            <circle cx="60" cy="140" r="6" fill="rgba(45,186,110,.15)" stroke="rgba(45,186,110,.2)"/>
                            <text x="60" y="160" class="is-soft">Pointe-Noire</text>
                            <text x="105" y="98" class="is-soft">Poto-Poto</text>
                            <text x="135" y="108" class="is-soft">Bacongo</text>
                            <text x="108" y="126" class="is-soft">Moungali</text>
                        </svg>
                        <div class="bd-colis-coverage__stats"><div><strong>7+</strong><span>Arrondissements</span></div><div><strong>Quotidien</strong><span>Frequence</span></div></div>
                    </div>
                </div>
            </div>
            <div class="bd-colis-coverage__panel" data-panel="1">
                <div class="bd-colis-coverage__info">
                    <div class="bd-colis-badge is-gold">Service actif</div>
                    <div class="bd-colis-coverage__city">Pointe-Noire</div>
                    <p>Envois structures avec verification renforcee. Destination a confirmer selon le mode de depot et la zone finale.</p>
                    <div class="bd-colis-coverage__items">
                        <div><span></span>Validation avant creation de l'envoi</div>
                        <div><span></span>Organisation de remise encadree et tracee</div>
                        <div><span></span>Support dedie a contacter pour les cas particuliers</div>
                    </div>
                </div>
                <div class="bd-colis-coverage__map">
                    <div class="bd-colis-coverage__map-head"><strong>Zone couverte</strong><span class="is-gold"><i></i>Pointe-Noire</span></div>
                    <div class="bd-colis-coverage__map-body">
                        <svg class="bd-colis-congo-svg" viewBox="0 0 320 200" fill="none">
                            <path d="M60 30 Q80 20 110 25 Q140 18 170 30 Q200 25 230 40 Q250 55 255 80 Q265 110 250 140 Q240 165 220 175 Q195 188 165 185 Q135 188 110 178 Q85 170 70 155 Q50 135 45 108 Q38 80 45 58 Q50 40 60 30Z" fill="rgba(45,186,110,.04)" stroke="rgba(45,186,110,.12)" stroke-width="1.5"/>
                            <circle cx="60" cy="140" r="16" fill="rgba(212,150,12,.15)" stroke="rgba(212,150,12,.4)"/>
                            <circle class="bd-colis-zone-ping-2" cx="60" cy="140" r="8" fill="#d4a020"/>
                            <text x="60" y="167">POINTE-NOIRE</text>
                            <circle cx="120" cy="115" r="6" fill="rgba(45,186,110,.08)" stroke="rgba(45,186,110,.15)"/>
                            <text x="120" y="105" class="is-soft">Brazzaville</text>
                        </svg>
                        <div class="bd-colis-coverage__stats"><div><strong class="is-gold">Encadre</strong><span>Mode remise</span></div><div><strong class="is-gold">Verification</strong><span>Avant envoi</span></div></div>
                    </div>
                </div>
            </div>
            <div class="bd-colis-coverage__panel" data-panel="2">
                <div class="bd-colis-coverage__info">
                    <div class="bd-colis-badge is-soft">En expansion</div>
                    <div class="bd-colis-coverage__city">Zones principales</div>
                    <p>Disponibilite selon la destination et les conditions d'accessibilite locales. Validation de la zone recommandee avant toute creation d'envoi.</p>
                    <div class="bd-colis-coverage__items">
                        <div><span></span>Validation de la zone recommandee avant expeditions</div>
                        <div><span></span>Conditions de remise etablies selon l'accessibilite</div>
                        <div><span></span>Contact support recommande pour verification rapide</div>
                    </div>
                </div>
                <div class="bd-colis-coverage__map">
                    <div class="bd-colis-coverage__map-head"><strong>Couverture nationale</strong><span class="is-soft"><i></i>En expansion</span></div>
                    <div class="bd-colis-coverage__map-body">
                        <svg class="bd-colis-congo-svg" viewBox="0 0 320 200" fill="none">
                            <path d="M60 30 Q80 20 110 25 Q140 18 170 30 Q200 25 230 40 Q250 55 255 80 Q265 110 250 140 Q240 165 220 175 Q195 188 165 185 Q135 188 110 178 Q85 170 70 155 Q50 135 45 108 Q38 80 45 58 Q50 40 60 30Z" fill="rgba(45,186,110,.05)" stroke="rgba(45,186,110,.15)" stroke-width="1.5"/>
                            <circle cx="120" cy="115" r="6" fill="#2dbb6e" opacity=".7"/>
                            <circle cx="60" cy="140" r="6" fill="#d4a020" opacity=".7"/>
                            <circle cx="180" cy="60" r="5" fill="#8a9086" opacity=".5"/>
                            <circle cx="200" cy="100" r="5" fill="#8a9086" opacity=".5"/>
                            <circle cx="140" cy="150" r="5" fill="#8a9086" opacity=".5"/>
                            <circle cx="80" cy="80" r="4" fill="#8a9086" opacity=".4"/>
                            <text x="160" y="185">RESEAU EN EXPANSION</text>
                        </svg>
                        <div class="bd-colis-coverage__stats"><div><strong class="is-soft">Variable</strong><span>Disponibilite</span></div><div><strong class="is-soft">Sur demande</strong><span>Validation zone</span></div></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bd-colis-conditions">
        <div class="bd-colis-wrap">
            <div class="bd-colis-tag bd-colis-tag--light">Regles d'utilisation</div>
            <h2>Ce qu'il faut <em>savoir</em></h2>
            <div class="bd-colis-conditions__grid">
                <div class="bd-colis-conditions__card">
                    <div class="bd-colis-conditions__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></div>
                    <div class="bd-colis-conditions__title">Conditions d'expedition</div>
                    <div class="bd-colis-conditions__list">
                        <div><span></span>Coordonnees completes de l'expediteur et du destinataire obligatoires</div>
                        <div><span></span>Poids et type de colis clairement renseignes avant validation</div>
                        <div><span></span>Declaration des articles fragiles, de valeur ou particuliers</div>
                    </div>
                </div>
                <div class="bd-colis-conditions__card">
                    <div class="bd-colis-conditions__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                    <div class="bd-colis-conditions__title">Conditions de remise</div>
                    <div class="bd-colis-conditions__list">
                        <div><span></span>Verification de l'identite du destinataire a la remise</div>
                        <div><span></span>Respect des consignes specifiques enregistrees par l'expediteur</div>
                        <div><span></span>Contact support immediat en cas d'absence ou d'incident</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bd-colis-links">
        <div class="bd-colis-wrap">
            <div class="bd-colis-tag">Ressources</div>
            <h2>Actualites, promotions,<br><span>parrainage, FAQ.</span></h2>
            <p>Toutes les informations utiles avant d'envoyer, pendant le transit ou apres la remise.</p>
            <div class="bd-colis-links__grid">
                <a class="bd-colis-links__card" href="{{ $newsUrl }}">
                    <div class="bd-colis-links__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
                    <div class="bd-colis-links__title">Actualites</div>
                    <div class="bd-colis-links__desc">Informations recentes sur le service colis, couverture et evolutions.</div>
                    <div class="bd-colis-links__arrow">Voir les actualites <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
                </a>
                <a class="bd-colis-links__card" href="{{ $offersUrl }}">
                    <div class="bd-colis-links__icon is-gold"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg></div>
                    <div class="bd-colis-links__title">Promotions</div>
                    <div class="bd-colis-links__desc">Offres en cours et avantages lies aux envois reguliers.</div>
                    <div class="bd-colis-links__arrow is-gold">Voir les offres <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
                </a>
                <a class="bd-colis-links__card" href="{{ $helpUrl }}">
                    <div class="bd-colis-links__icon is-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                    <div class="bd-colis-links__title">Parrainage</div>
                    <div class="bd-colis-links__desc">Centre d'aide et accompagnement autour du service Mema.</div>
                    <div class="bd-colis-links__arrow is-blue">Voir les conditions <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
                </a>
                <a class="bd-colis-links__card" href="{{ $faqUrl }}">
                    <div class="bd-colis-links__icon is-dark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
                    <div class="bd-colis-links__title">FAQ</div>
                    <div class="bd-colis-links__desc">Questions frequentes sur delais, couverture, remise, paiement et incidents.</div>
                    <div class="bd-colis-links__arrow is-dark">Consulter la FAQ <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
                </a>
            </div>
        </div>
    </section>

    @if($colisOpportunities->isNotEmpty())
    <section class="bd-colis-opportunities">
        <div class="bd-colis-wrap">
            <div class="bd-colis-tag">{{ $homeContent['opportunities_tag'] ?? 'Opportunites logistiques' }}</div>
            <h2>{{ $homeContent['opportunities_title'] ?? 'Grandissez avec Mema' }}</h2>
            <p>{{ $homeContent['opportunities_subtitle'] ?? 'Developpez vos operations de livraison, de relais ou de traitement colis.' }}</p>
            <div class="bd-colis-opportunities__grid">
                @foreach($colisOpportunities as $item)
                <article class="bd-colis-opportunity">
                    @if(!empty($item['image']))
                        <div class="bd-colis-opportunity__media">
                            <img src="{{ $item['image'] }}" alt="">
                        </div>
                    @endif
                    <div class="bd-colis-opportunity__content">
                        <div class="bd-colis-opportunity__title">{{ $item['title'] }}</div>
                        <div class="bd-colis-opportunity__body">{{ $item['body'] }}</div>
                        <a href="{{ $item['url'] }}" class="bd-colis-opportunity__cta">{{ $item['cta'] }}</a>
                    </div>
                </article>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <section class="bd-colis-cta">
        <canvas id="bdColisCtaCanvas"></canvas>
        <div class="bd-colis-wrap">
            <div class="bd-colis-cta__inner">
                <div class="bd-colis-cta__text">
                    <div class="bd-colis-tag bd-colis-tag--light">Assistance</div>
                    <h2>{{ $colisSupportTitle }}</h2>
                    <p>{{ $colisSupportDescription }}</p>
                    <div class="bd-colis-cta__buttons">
                        <a class="bd-colis-btn-primary" href="{{ $contactUrl }}">{{ $colisSupportCta }} <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                        <a class="bd-colis-btn-red" href="{{ $claimUrl }}">Reclamer un colis</a>
                    </div>
                </div>
                <div class="bd-colis-cta__status">
                    <div class="bd-colis-cta__status-title">Etat du service</div>
                    <div class="bd-colis-cta__status-row"><div class="bd-colis-cta__dot is-ok"></div><div class="bd-colis-cta__label">Mema — Brazzaville</div><div class="bd-colis-cta__val is-ok">Operationnel</div></div>
                    <div class="bd-colis-cta__status-row"><div class="bd-colis-cta__dot is-ok"></div><div class="bd-colis-cta__label">Mema — Pointe-Noire</div><div class="bd-colis-cta__val is-ok">Operationnel</div></div>
                    <div class="bd-colis-cta__status-row"><div class="bd-colis-cta__dot is-warn"></div><div class="bd-colis-cta__label">Zones principales</div><div class="bd-colis-cta__val is-warn">Validation requise</div></div>
                    <div class="bd-colis-cta__status-row"><div class="bd-colis-cta__dot is-ok"></div><div class="bd-colis-cta__label">Suivi GPS</div><div class="bd-colis-cta__val is-ok">Actif</div></div>
                    <a class="bd-colis-cta__hotline" href="{{ $helpUrl }}">
                        <div class="bd-colis-cta__hotline-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.48 2 2 0 0 1 3.58 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6.13 6.13l1.92-1.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
                        <div>
                            <div class="bd-colis-cta__hotline-label">Support WhatsApp disponible</div>
                            <div class="bd-colis-cta__hotline-sub">Reponse rapide pour les colis en transit</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <footer class="bd-colis-footer">
        <div class="bd-colis-footer__grid">
            <div class="bd-colis-footer__brand">
                <div class="bd-colis-footer__logo"><span class="bd-colis-nav__mark is-light"></span>Mema</div>
                <p>Mema vous aide a expedier, suivre et reclamer un envoi avec un parcours clair du depot jusqu'a la remise.</p>
                <div class="bd-colis-footer__socials">
                    <a href="{{ $contactUrl }}" aria-label="Support Mema"><i class="fas fa-headset"></i></a>
                    <a href="{{ $helpUrl }}" aria-label="Aide Mema"><i class="fas fa-circle-question"></i></a>
                    <a href="https://wa.me/242064000000" aria-label="WhatsApp" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp"></i></a>
                    <a href="{{ $privacyUrl }}" aria-label="Confidentialité Mema"><i class="fas fa-shield-halved"></i></a>
                </div>
            </div>
            <div class="bd-colis-footer__col">
                <h4>Liens rapides</h4>
                <div class="bd-colis-footer__links">
                    <a href="{{ $colisLandingUrl }}">Accueil Mema</a>
                    <a href="{{ $colisLandingUrl }}#bdColisTrackForm">Suivre un envoi</a>
                    <a href="{{ $colisCreateUrl }}">Expedier un colis</a>
                    <a href="{{ $claimUrl }}">Faire une reclamation</a>
                    <a href="{{ $aboutUrl }}">A propos de Mema</a>
                    <a href="{{ $contactUrl }}">Contacter le support colis</a>
                </div>
            </div>
            <div class="bd-colis-footer__col">
                <h4>Informations</h4>
                <div class="bd-colis-footer__links">
                    <a href="{{ route('terms.conditions', ['brand' => 'mema']) }}">Conditions generales</a>
                    <a href="{{ $privacyUrl }}">Politique de confidentialite</a>
                    <a href="{{ $refundUrl }}">Politique de remboursement</a>
                    <a href="{{ $faqUrl }}">FAQ</a>
                    <a href="{{ $helpUrl }}">Centre d'aide</a>
                    <a href="{{ $dataDeletionUrl }}">Suppression des donnees</a>
                </div>
            </div>
            <div class="bd-colis-footer__col">
                <h4>Ressources</h4>
                <div class="bd-colis-footer__links">
                    <a href="{{ route('colis.track_public') }}">Suivre un colis</a>
                    <a href="{{ $colisCreateUrl }}">Expedier un colis</a>
                    <a href="{{ $helpUrl }}">Centre d'aide</a>
                    <a href="{{ $siteMapUrl }}">Plan du site</a>
                    <a href="{{ $contactUrl }}">Nous contacter</a>
                </div>
            </div>
        </div>
        <div class="bd-colis-footer__bottom">
            <div class="bd-colis-footer__copy">© 2026 Mema. Tous droits reserves. Republique du Congo.</div>
            <div class="bd-colis-footer__pay">
                <span>Mobile Money</span>
                <span>Airtel Money</span>
                <span>MTN MoMo</span>
                <span>Cash</span>
            </div>
            <div class="bd-colis-footer__legal">
                <a href="{{ $legalUrl }}">Mentions legales</a>
                <a href="{{ $cookiesUrl }}">Cookies</a>
                <a href="{{ $siteMapUrl }}">Plan du site</a>
            </div>
        </div>
    </footer>
</div>
@endsection

@section('styles')
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    .modern-header,
    .modern-footer { display: none !important; }
    body.bd-future-shell.bd-colis-landing { background: #f0f5f0; font-family: 'Manrope', sans-serif; cursor: none; }
    body.bd-future-shell main { overflow: visible; }
    .bd-colis-page { --bg:#f0f5f0; --bg2:#f8faf8; --bg3:#eaf0ea; --bg4:#dfe8df; --green:#1b6e42; --green-dark:#134f2e; --green-mid:#22894f; --green-pale:rgba(27,110,66,.07); --green-glow:rgba(27,110,66,.18); --colis-primary:#009543; --colis-secondary:#22c55e; --colis-pale:rgba(0,149,67,.08); --colis-glow:rgba(0,149,67,.22); --colis-dark:#007836; --gold:#c08a10; --gold-light:#d4a020; --gold-pale:rgba(192,138,16,.08); --text:#181a16; --text2:#4a5046; --text3:#8a9086; --blue:#2563b8; --blue-pale:rgba(37,99,184,.07); --red:#c0392b; --border:rgba(24,26,22,.08); --border2:rgba(24,26,22,.15); --sh:0 2px 14px rgba(24,26,22,.06); --sh-md:0 6px 28px rgba(24,26,22,.09); --sh-lg:0 20px 60px rgba(24,26,22,.11); --r-s:6px; --r-m:12px; --r-l:20px; --r-xl:28px; background:var(--bg); color:var(--text); overflow-x:hidden; -webkit-font-smoothing:antialiased; }
    .bd-colis-page * { box-sizing:border-box; }
    .bd-colis-wrap { max-width:1160px; margin:0 auto; padding:0 3rem; }
    #bd-colis-cursor { position:fixed; inset:0; z-index:9999; pointer-events:none; }
    #bd-colis-cursor-dot { width:8px; height:8px; background:var(--green); border-radius:50%; position:absolute; transform:translate(-50%,-50%); }
    #bd-colis-cursor-ring { width:32px; height:32px; border:1px solid rgba(26,107,64,.35); border-radius:50%; position:absolute; transform:translate(-50%,-50%); transition:transform .18s cubic-bezier(.25,.46,.45,.94),width .2s,height .2s,border-color .2s; }
    body:has(.bd-colis-page a:hover) #bd-colis-cursor-ring, body:has(.bd-colis-page button:hover) #bd-colis-cursor-ring { width:46px; height:46px; border-color:var(--green); }
    .bd-colis-wa-float { position:fixed; bottom:2rem; right:2rem; z-index:200; width:54px; height:54px; border-radius:50%; background:#25d366; color:#fff; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 20px rgba(37,211,102,.4); animation:bdColisWaFloat 3.5s ease-in-out infinite; }
    .bd-colis-wa-float svg { width:24px; height:24px; }
    @keyframes bdColisWaFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-5px)} }
    .bd-colis-ticker { background:var(--colis-dark); height:34px; overflow:hidden; display:flex; align-items:center; border-bottom:1px solid rgba(0,149,67,.25); position:relative; z-index:100; }
    .bd-colis-ticker__track { display:flex; white-space:nowrap; animation:bdColisTickerRoll 32s linear infinite; }
    .bd-colis-ticker__item { display:inline-flex; align-items:center; gap:10px; padding:0 3rem; font-size:.68rem; font-weight:600; color:rgba(255,255,255,.8); letter-spacing:.12em; text-transform:uppercase; }
    .bd-colis-ticker__gem { width:4px; height:4px; background:#fff; border-radius:50%; flex-shrink:0; opacity:.6; }
    @keyframes bdColisTickerRoll { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }
    .bd-colis-nav { position:fixed; top:34px; left:0; right:0; z-index:180; display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 3rem; background:rgba(240,245,240,.96); backdrop-filter:blur(28px); border-bottom:1px solid var(--border); transition:padding .3s, box-shadow .3s; }
    .bd-colis-nav.is-compact { padding:.72rem 3rem; box-shadow:0 4px 24px rgba(24,26,22,.06); }
    .bd-colis-nav__logo { display:flex; align-items:center; gap:.6rem; font-family:'Cormorant Garamond', serif; font-size:1.4rem; font-weight:600; color:var(--text); text-decoration:none; }
    .bd-colis-nav__mark { width:9px; height:9px; border-radius:50%; background:var(--colis-primary); box-shadow:0 0 10px var(--colis-glow); flex-shrink:0; }
    .bd-colis-nav__mark.is-light { background:#fff; box-shadow:0 0 10px rgba(255,255,255,.3); }
    .bd-colis-nav__links, .bd-colis-nav__end { display:flex; align-items:center; gap:.35rem; }
    .bd-colis-nav__links a, .bd-colis-nav__track { padding:.42rem 1rem; font-size:.8rem; font-weight:600; color:var(--text2); border-radius:50px; text-decoration:none; transition:.15s; }
    .bd-colis-nav__links a:hover, .bd-colis-nav__track:hover { color:var(--text); background:var(--bg4); }
    .bd-colis-nav__links a.is-active { color:var(--green); background:var(--green-pale); }
    .bd-colis-nav__lang { font-size:.72rem; font-weight:700; color:var(--text3); padding:.35rem .7rem; border:1px solid var(--border); border-radius:50px; text-decoration:none; }
    .bd-colis-nav__cta { background:var(--colis-primary); color:#fff; padding:.58rem 1.5rem; border-radius:50px; font-size:.8rem; font-weight:800; text-decoration:none; letter-spacing:.02em; box-shadow:0 3px 14px var(--colis-glow); }
    .bd-colis-tag { display:inline-flex; align-items:center; gap:8px; margin-bottom:1rem; font-size:.66rem; font-weight:700; letter-spacing:.22em; text-transform:uppercase; color:var(--green); }
    .bd-colis-tag::before { content:''; width:18px; height:1.5px; background:var(--colis-primary); border-radius:2px; }
    .bd-colis-tag--light { color:rgba(255,255,255,.55); }
    .bd-colis-tag--light::before { background:var(--colis-primary); }
    .bd-colis-hero { min-height:100vh; display:flex; align-items:center; position:relative; overflow:hidden; background:#f0f5f0; background-position:72% center; background-repeat:no-repeat; background-size:cover; }
    .bd-colis-hero__overlay, #bdColisHeroCanvas, .bd-colis-hero__grid, .bd-colis-hero__geo { position:absolute; inset:0; }
    .bd-colis-hero__overlay { z-index:1; pointer-events:none; background:linear-gradient(105deg, rgba(240,245,240,.985) 0%, rgba(240,245,240,.95) 28%, rgba(240,245,240,.76) 50%, rgba(240,245,240,.26) 72%, rgba(240,245,240,.05) 100%); }
    #bdColisHeroCanvas { z-index:2; pointer-events:none; }
    .bd-colis-hero__geo { right:-100px; top:-100px; left:auto; width:700px; height:700px; border-radius:50%; pointer-events:none; z-index:1; background:radial-gradient(ellipse at 35% 35%, rgba(26,107,64,.04) 0%, transparent 70%); }
    .bd-colis-hero__grid { z-index:1; pointer-events:none; background-image:linear-gradient(rgba(26,107,64,.03) 1px, transparent 1px), linear-gradient(90deg, rgba(26,107,64,.03) 1px, transparent 1px); background-size:80px 80px; mask-image:radial-gradient(ellipse at 30% 50%, black 5%, transparent 55%); }
    .bd-colis-hero__inner { position:relative; z-index:3; max-width:1160px; margin:0 auto; width:100%; padding:8rem 3rem 5rem; display:grid; grid-template-columns:1fr 1fr; gap:4rem; align-items:center; }
    .bd-colis-reveal { opacity:0; transform:translateY(24px); animation:bdColisReveal .75s cubic-bezier(.22,1,.36,1) forwards; }
    .bd-colis-reveal-1 { animation-delay:.08s; } .bd-colis-reveal-2 { animation-delay:.2s; } .bd-colis-reveal-3 { animation-delay:.34s; } .bd-colis-reveal-4 { animation-delay:.48s; } .bd-colis-reveal-5 { animation-delay:.62s; } .bd-colis-reveal-6 { animation-delay:.76s; }
    @keyframes bdColisReveal { from{opacity:0; transform:translateY(24px)} to{opacity:1; transform:translateY(0)} }
    .bd-colis-breadcrumb { display:flex; align-items:center; gap:.5rem; font-size:.72rem; color:var(--text3); margin-bottom:1.4rem; }
    .bd-colis-breadcrumb a { color:var(--text3); text-decoration:none; }
    .bd-colis-breadcrumb__sep svg { width:12px; height:12px; }
    .bd-colis-hero__title { font-family:'Cormorant Garamond', serif; font-weight:300; font-size:clamp(3.2rem,6vw,5.8rem); line-height:.93; letter-spacing:-.02em; color:var(--text); margin-bottom:1.6rem; }
    .bd-colis-hero__title em, .bd-colis-trust h2 em, .bd-colis-coverage h2 em, .bd-colis-conditions h2 em, .bd-colis-cta h2 em { font-style:italic; color:var(--green); font-weight:300; }
    .bd-colis-hero__subtitle { font-size:.97rem; color:var(--text2); line-height:1.85; max-width:460px; margin-bottom:2.4rem; }
    .bd-colis-track-bar { display:flex; align-items:center; background:var(--bg3); border:1px solid var(--border2); border-radius:50px; overflow:hidden; margin-bottom:1.8rem; max-width:480px; }
    .bd-colis-track-bar__input { flex:1; background:transparent; border:none; outline:none; padding:.9rem 1.3rem; font-family:inherit; font-size:.84rem; color:var(--text); letter-spacing:.04em; }
    .bd-colis-track-bar__button { background:var(--colis-primary); color:#fff; border:none; padding:.9rem 1.6rem; font-weight:800; font-size:.8rem; letter-spacing:.03em; border-radius:0 50px 50px 0; white-space:nowrap; }
    .bd-colis-track-result { display:none; margin-top:1rem; padding:.85rem 1.2rem; background:var(--bg3); border:1px solid rgba(45,186,110,.25); border-radius:var(--r-m); font-size:.82rem; color:var(--green); font-weight:600; max-width:480px; }
    .bd-colis-quick-actions { display:flex; gap:.75rem; flex-wrap:wrap; }
    .bd-colis-quick-actions__btn { display:flex; align-items:center; gap:.6rem; padding:.6rem 1.1rem; border-radius:50px; font-size:.78rem; font-weight:600; border:1px solid var(--border2); background:transparent; color:var(--text2); text-decoration:none; transition:.2s; }
    .bd-colis-quick-actions__btn svg { width:14px; height:14px; }
    .bd-colis-quick-actions__btn:hover { background:var(--bg4); border-color:var(--border); color:var(--text); }
    .bd-colis-quick-actions__btn.is-primary { background:var(--colis-pale); border-color:rgba(0,149,67,.25); color:var(--colis-primary); }
    .bd-colis-quick-actions__btn.is-primary:hover { background:var(--colis-primary); color:#fff; border-color:var(--colis-primary); }
    .bd-colis-hero__visual { position:relative; display:flex; justify-content:center; }
    .bd-colis-track-panel { width:100%; max-width:400px; border-radius:var(--r-xl); background:var(--bg3); border:1px solid var(--border); box-shadow:var(--sh-lg); overflow:hidden; }
    .bd-colis-track-panel__header { background:var(--colis-primary); padding:1.4rem 1.8rem; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid rgba(255,255,255,.15); }
    .bd-colis-track-panel__title { font-family:'Cormorant Garamond', serif; font-size:1rem; font-weight:400; color:#fff; }
    .bd-colis-track-panel__id { font-family:'Courier New', monospace; font-size:.68rem; color:rgba(255,255,255,.8); letter-spacing:.1em; background:rgba(255,255,255,.15); padding:3px 9px; border-radius:4px; }
    .bd-colis-track-panel__body { padding:1.6rem 1.8rem; }
    .bd-colis-track-panel__steps { display:flex; flex-direction:column; }
    .bd-colis-track-panel__step { display:flex; gap:1rem; align-items:flex-start; }
    .bd-colis-track-panel__left { display:flex; flex-direction:column; align-items:center; width:26px; flex-shrink:0; }
    .bd-colis-track-panel__dot { width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; z-index:1; }
    .bd-colis-track-panel__dot svg { width:11px; height:11px; }
    .bd-colis-track-panel__dot.is-done { background:var(--green); }
    .bd-colis-track-panel__dot.is-active { background:var(--colis-primary); box-shadow:0 0 0 4px rgba(0,149,67,.18); }
    .bd-colis-track-panel__dot.is-next { background:var(--bg4); border:1.5px solid var(--border2); }
    .bd-colis-track-panel__dot-core { width:8px; height:8px; background:#000; border-radius:50%; } .bd-colis-track-panel__dot-core.is-next { width:7px; height:7px; background:var(--bg2); }
    .bd-colis-track-panel__line { width:1.5px; flex:1; min-height:28px; } .bd-colis-track-panel__line.is-done { background:rgba(45,186,110,.2); } .bd-colis-track-panel__line.is-pending { background:var(--border); }
    .bd-colis-track-panel__content { padding:.15rem 0 1.6rem; flex:1; }
    .bd-colis-track-panel__label { font-size:.82rem; font-weight:700; color:var(--text); margin-bottom:2px; }
    .bd-colis-track-panel__label.is-active { color:var(--gold); } .bd-colis-track-panel__label.is-muted { color:var(--text3); font-weight:500; }
    .bd-colis-track-panel__time { font-size:.68rem; color:var(--text3); }
    .bd-colis-track-panel__note { font-size:.7rem; color:var(--text2); margin-top:3px; line-height:1.5; }
    .bd-colis-track-panel__divider { height:1px; background:var(--border); margin:1.2rem 0; }
    .bd-colis-track-panel__meta { display:grid; grid-template-columns:repeat(3,1fr); gap:.8rem; }
    .bd-colis-track-panel__meta span { display:block; font-size:.62rem; text-transform:uppercase; letter-spacing:.1em; color:var(--text3); font-weight:700; margin-bottom:3px; }
    .bd-colis-track-panel__meta strong { font-size:.8rem; font-weight:700; color:var(--text); } .bd-colis-track-panel__meta .is-green { color:var(--green); }
    .bd-colis-floating-card { position:absolute; background:var(--bg3); border:1px solid var(--border2); border-radius:var(--r-m); padding:.8rem 1.1rem; box-shadow:var(--sh); display:flex; align-items:center; gap:.8rem; animation:bdColisFloating 4s ease-in-out infinite; z-index:10; }
    .bd-colis-floating-card--proof { bottom:-18px; left:-36px; } .bd-colis-floating-card--gps { top:-16px; right:-28px; animation-delay:1.7s; }
    @keyframes bdColisFloating { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-7px)} }
    .bd-colis-floating-card__icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .bd-colis-floating-card__icon svg { width:16px; height:16px; } .bd-colis-floating-card__icon.is-green { background:var(--green-pale); color:var(--green); } .bd-colis-floating-card__icon.is-gold { background:var(--gold-pale); color:var(--gold); }
    .bd-colis-floating-card__label { font-size:.74rem; font-weight:700; color:var(--text); } .bd-colis-floating-card__sub { font-size:.64rem; color:var(--text2); margin-top:1px; }
    .bd-colis-scroll-indicator { position:absolute; bottom:2.5rem; left:50%; transform:translateX(-50%); display:flex; flex-direction:column; align-items:center; gap:.5rem; z-index:2; animation:bdColisScrollFade 2s ease-in-out infinite; }
    @keyframes bdColisScrollFade { 0%,100%{opacity:.35; transform:translateX(-50%) translateY(0)} 50%{opacity:1; transform:translateX(-50%) translateY(5px)} }
    .bd-colis-scroll-indicator__line { width:1px; height:44px; background:linear-gradient(to bottom,var(--green),transparent); }
    .bd-colis-scroll-indicator__txt { font-size:.58rem; font-weight:700; letter-spacing:.2em; text-transform:uppercase; color:var(--text3); }
    .bd-colis-actions { padding:0 0 5rem; background:#f4f8f5; border-top:1px solid rgba(27,110,66,.08); }
    .bd-colis-actions__grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1px; background:var(--border); border:1px solid var(--border); border-radius:var(--r-xl); overflow:hidden; box-shadow:var(--sh-md); max-width:1160px; margin:-1px auto 0; }
    .bd-colis-actions__cell { background:var(--bg2); padding:2.4rem 2rem; position:relative; overflow:hidden; transition:background .25s; display:flex; flex-direction:column; gap:.8rem; color:inherit; text-decoration:none; }
    .bd-colis-actions__cell:hover { background:var(--bg3); }
    .bd-colis-actions__cell::after { content:''; position:absolute; bottom:0; left:0; right:0; height:3px; background:linear-gradient(90deg,var(--green),var(--green-mid)); transform:scaleX(0); transform-origin:left; transition:transform .35s cubic-bezier(.22,1,.36,1); }
    .bd-colis-actions__cell:hover::after { transform:scaleX(1); }
    .bd-colis-actions__num { position:absolute; top:.8rem; right:1.2rem; font-family:'Cormorant Garamond', serif; font-size:5rem; font-weight:300; color:rgba(27,110,66,.05); line-height:1; }
    .bd-colis-actions__icon { width:46px; height:46px; border-radius:var(--r-s); border:1px solid var(--border); background:var(--bg4); display:flex; align-items:center; justify-content:center; transition:.2s; }
    .bd-colis-actions__icon svg { width:20px; height:20px; color:var(--green); }
    .bd-colis-actions__cell:hover .bd-colis-actions__icon { border-color:rgba(27,110,66,.3); background:var(--green-pale); transform:scale(1.06); }
    .bd-colis-actions__title { font-family:'Cormorant Garamond', serif; font-size:1.1rem; font-weight:400; color:var(--text); }
    .bd-colis-actions__desc { font-size:.78rem; color:var(--text2); line-height:1.75; }
    .bd-colis-actions__link { display:inline-flex; align-items:center; gap:.45rem; font-size:.74rem; font-weight:700; color:var(--green); margin-top:auto; transition:gap .25s; }
    .bd-colis-actions__link svg { width:13px; height:13px; }
    .bd-colis-actions__cell:hover .bd-colis-actions__link { gap:.8rem; }
    .bd-colis-trust { padding:7rem 0; background:#f8faf8; border-top:1px solid var(--border); }
    .bd-colis-trust__grid { display:grid; grid-template-columns:1.1fr 1fr; gap:5rem; align-items:center; }
    .bd-colis-trust h2, .bd-colis-coverage h2, .bd-colis-conditions h2, .bd-colis-links h2, .bd-colis-cta h2 { font-family:'Cormorant Garamond', serif; font-size:clamp(2.2rem,3.8vw,3.2rem); font-weight:300; line-height:1.1; color:var(--text); margin-bottom:1.2rem; }
    .bd-colis-trust p, .bd-colis-links p { font-size:.92rem; color:var(--text2); line-height:1.85; margin-bottom:2rem; max-width:440px; }
    .bd-colis-trust__points { display:flex; flex-direction:column; gap:.8rem; margin-bottom:2.2rem; }
    .bd-colis-trust__point { display:flex; align-items:flex-start; gap:.85rem; font-size:.86rem; color:var(--text2); }
    .bd-colis-trust__check { width:22px; height:22px; border-radius:50%; background:var(--green-pale); display:flex; align-items:center; justify-content:center; flex-shrink:0; color:var(--green); margin-top:1px; }
    .bd-colis-trust__check svg { width:10px; height:10px; }
    .bd-colis-trust__buttons { display:flex; gap:.9rem; flex-wrap:wrap; }
    .bd-colis-testimonials { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; margin-top:1.4rem; }
    .bd-colis-testimonial { background:#fff; border:1px solid var(--border); border-radius:var(--r-m); padding:1rem 1rem .9rem; box-shadow:0 18px 42px rgba(10,38,20,.06); }
    .bd-colis-testimonial__tag { font-size:.68rem; letter-spacing:.12em; text-transform:uppercase; color:var(--text3); font-weight:800; }
    .bd-colis-testimonial__quote { margin:.7rem 0 .8rem; color:var(--text); font-size:.88rem; line-height:1.7; }
    .bd-colis-testimonial__meta { font-size:.78rem; color:var(--text2); }
    .bd-colis-btn-primary, .bd-colis-btn-outline, .bd-colis-btn-red { display:inline-flex; align-items:center; gap:.5rem; text-decoration:none; }
    .bd-colis-btn-primary { background:var(--colis-primary); color:#fff; padding:.85rem 2rem; border-radius:50px; font-weight:800; font-size:.86rem; box-shadow:0 4px 18px var(--colis-glow); }
    .bd-colis-btn-primary svg { width:13px; height:13px; }
    .bd-colis-btn-outline { background:transparent; color:var(--green); padding:.85rem 1.8rem; border-radius:50px; font-weight:600; font-size:.84rem; border:1.5px solid rgba(27,110,66,.3); }
    .bd-colis-btn-red { background:rgba(255,255,255,.1); color:#fff; border:1px solid rgba(255,255,255,.25); padding:.85rem 1.8rem; border-radius:50px; font-weight:700; font-size:.84rem; }
    .bd-colis-proof-visual { position:relative; }
    .bd-colis-proof-screen { background:#fff; border:1px solid var(--border); border-radius:var(--r-xl); overflow:hidden; box-shadow:var(--sh-lg); }
    .bd-colis-proof-screen__header { background:var(--colis-primary); padding:1.2rem 1.6rem; border-bottom:1px solid rgba(255,255,255,.15); display:flex; align-items:center; justify-content:space-between; }
    .bd-colis-proof-screen__title { font-family:'Cormorant Garamond', serif; font-size:.92rem; color:#fff; }
    .bd-colis-proof-screen__live { display:flex; align-items:center; gap:5px; font-size:.68rem; font-weight:600; color:rgba(255,255,255,.8); }
    .bd-colis-proof-screen__live span { width:6px; height:6px; background:#fff; border-radius:50%; animation:bdColisPulse 2s ease-in-out infinite; }
    @keyframes bdColisPulse { 0%,100%{opacity:1} 50%{opacity:.4} }
    .bd-colis-proof-screen__body { padding:1.4rem; }
    .bd-colis-progress-track { display:flex; align-items:center; gap:0; margin-bottom:1.4rem; }
    .bd-colis-progress-track__step { display:flex; align-items:center; flex:1; position:relative; }
    .bd-colis-progress-track__node { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; z-index:1; }
    .bd-colis-progress-track__node svg { width:11px; height:11px; }
    .bd-colis-progress-track__node.is-done { background:var(--green); }
    .bd-colis-progress-track__node.is-active { background:var(--colis-primary); box-shadow:0 0 0 4px rgba(0,149,67,.15); }
    .bd-colis-progress-track__node.is-pending { background:var(--bg4); border:1.5px solid var(--border2); }
    .bd-colis-progress-track__core { width:8px; height:8px; background:#000; border-radius:50%; }
    .bd-colis-progress-track__core.is-pending { width:7px; height:7px; background:var(--bg2); }
    .bd-colis-progress-track__bar { flex:1; height:2px; } .bd-colis-progress-track__bar.is-done { background:var(--green); } .bd-colis-progress-track__bar.is-pending { background:var(--border); }
    .bd-colis-progress-labels { display:flex; justify-content:space-between; margin-bottom:1.2rem; }
    .bd-colis-progress-labels span { font-size:.66rem; font-weight:600; color:var(--text3); letter-spacing:.05em; text-transform:uppercase; text-align:center; flex:1; }
    .bd-colis-progress-labels .is-active { color:var(--colis-primary); } .bd-colis-progress-labels .is-done { color:var(--green); }
    .bd-colis-proof-screen__divider { height:1px; background:var(--border); margin:1rem 0; }
    .bd-colis-proof-row { display:flex; gap:.8rem; align-items:center; padding:.75rem; background:var(--bg4); border-radius:var(--r-m); border:1px solid var(--border); }
    .bd-colis-proof-row__icon { width:38px; height:38px; border-radius:var(--r-s); background:var(--green-pale); display:flex; align-items:center; justify-content:center; flex-shrink:0; color:var(--green); }
    .bd-colis-proof-row__icon svg { width:18px; height:18px; }
    .bd-colis-proof-row__label { font-size:.78rem; font-weight:700; color:var(--text); } .bd-colis-proof-row__sub { font-size:.66rem; color:var(--text2); margin-top:2px; }
    .bd-colis-proof-row__badge { margin-left:auto; background:var(--colis-primary); color:#fff; font-size:.6rem; font-weight:800; padding:3px 9px; border-radius:50px; white-space:nowrap; }
    .bd-colis-scan-container { position:relative; margin-top:1rem; border-radius:var(--r-m); overflow:hidden; background:var(--bg4); border:1px solid var(--border); }
    .bd-colis-scan-container__line { position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg, transparent, var(--colis-primary), transparent); animation:bdColisScanMove 2.5s ease-in-out infinite; }
    @keyframes bdColisScanMove { 0%,100%{top:8px; opacity:.8} 50%{top:calc(100% - 8px); opacity:1} }
    .bd-colis-scan-container__content { padding:1rem; display:flex; align-items:center; gap:.85rem; }
    .bd-colis-barcode { flex:1; height:40px; display:flex; align-items:center; gap:1.5px; }
    .bd-colis-barcode .b { width:2px; background:var(--text2); border-radius:1px; opacity:.5; } .bd-colis-barcode .w { width:4px; } .bd-colis-barcode .h26 { height:26px; } .bd-colis-barcode .h28 { height:28px; } .bd-colis-barcode .h30 { height:30px; } .bd-colis-barcode .h32 { height:32px; } .bd-colis-barcode .h34 { height:34px; } .bd-colis-barcode .h36 { height:36px; } .bd-colis-barcode .h38 { height:38px; }
    .bd-colis-scan-container__number { font-family:'Courier New', monospace; font-size:.68rem; color:var(--colis-primary); letter-spacing:.1em; margin-top:6px; text-align:center; }
    .bd-colis-scan-container__meta { margin-left:auto; text-align:right; }
    .bd-colis-scan-container__meta div { font-size:.68rem; font-weight:700; color:var(--text3); margin-bottom:3px; letter-spacing:.06em; text-transform:uppercase; }
    .bd-colis-scan-container__meta strong { font-size:.72rem; color:var(--green); font-weight:700; }
    .bd-colis-proof-floating { position:absolute; background:#fff; border:1px solid var(--border2); border-radius:var(--r-m); padding:.75rem 1rem; box-shadow:var(--sh-md); display:flex; align-items:center; gap:.75rem; animation:bdColisFloating 4s ease-in-out infinite; }
    .bd-colis-proof-floating--right { bottom:-16px; right:-30px; animation-delay:.5s; } .bd-colis-proof-floating--left { top:30px; left:-40px; animation-delay:2s; }
    .bd-colis-proof-floating__icon { width:30px; height:30px; border-radius:7px; display:flex; align-items:center; justify-content:center; flex-shrink:0; } .bd-colis-proof-floating__icon.is-green { background:var(--green-pale); color:var(--green); } .bd-colis-proof-floating__icon.is-gold { background:var(--gold-pale); color:var(--gold); }
    .bd-colis-proof-floating__icon svg { width:16px; height:16px; }
    .bd-colis-proof-floating__label { font-size:.72rem; font-weight:700; color:var(--text); } .bd-colis-proof-floating__sub { font-size:.62rem; color:var(--text2); margin-top:1px; }
    .bd-colis-coverage { padding:7rem 0; background:var(--green-dark); position:relative; overflow:hidden; }
    .bd-colis-coverage::before { content:''; position:absolute; inset:0; background:radial-gradient(ellipse at 80% 50%, rgba(34,137,79,.35) 0%, transparent 60%), radial-gradient(ellipse at 10% 80%, rgba(0,149,67,.08) 0%, transparent 50%); pointer-events:none; }
    .bd-colis-coverage > .bd-colis-wrap { position:relative; z-index:1; }
    .bd-colis-coverage h2 { color:#fff; } .bd-colis-coverage h2 em { color:rgba(255,255,255,.6); } .bd-colis-coverage p { color:rgba(255,255,255,.55); font-size:.9rem; line-height:1.8; max-width:560px; margin-bottom:2.4rem; }
    .bd-colis-coverage__tabs { display:flex; gap:.5rem; margin-bottom:2.8rem; }
    .bd-colis-coverage__tab { padding:.58rem 1.3rem; border-radius:50px; font-size:.8rem; font-weight:600; border:1px solid rgba(255,255,255,.15); background:rgba(255,255,255,.06); color:rgba(255,255,255,.6); transition:.2s; }
    .bd-colis-coverage__tab.is-active { background:var(--colis-primary); border-color:var(--colis-primary); color:#fff; box-shadow:0 4px 14px var(--colis-glow); }
    .bd-colis-coverage__panel { display:none; grid-template-columns:1fr 1fr; gap:3.5rem; align-items:start; }
    .bd-colis-coverage__panel.is-active { display:grid; }
    .bd-colis-badge { display:inline-flex; align-items:center; gap:6px; margin-bottom:1.2rem; font-size:.66rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; padding:4px 12px; border-radius:50px; }
    .bd-colis-badge.is-active { background:rgba(0,149,67,.2); color:var(--colis-secondary); border:1px solid rgba(0,149,67,.3); }
    .bd-colis-badge.is-gold { background:rgba(212,160,32,.15); color:var(--gold-light); border:1px solid rgba(212,160,32,.25); }
    .bd-colis-badge.is-soft { background:rgba(255,255,255,.08); color:rgba(255,255,255,.45); border:1px solid rgba(255,255,255,.15); }
    .bd-colis-coverage__city { font-family:'Cormorant Garamond', serif; font-size:2.4rem; font-weight:300; color:#fff; margin-bottom:.8rem; }
    .bd-colis-coverage__info p { font-size:.9rem; color:rgba(255,255,255,.55); line-height:1.85; margin-bottom:1.8rem; }
    .bd-colis-coverage__items { display:flex; flex-direction:column; gap:.7rem; }
    .bd-colis-coverage__items div { display:flex; align-items:flex-start; gap:.85rem; font-size:.84rem; color:rgba(255,255,255,.6); }
    .bd-colis-coverage__items span { width:20px; height:20px; border-radius:50%; background:rgba(255,255,255,.1); display:flex; flex-shrink:0; margin-top:1px; position:relative; }
    .bd-colis-coverage__items span::after { content:''; width:8px; height:8px; border-radius:50%; background:#fff; position:absolute; inset:6px; }
    .bd-colis-coverage__map { background:rgba(255,255,255,.06); border-radius:var(--r-xl); border:1px solid rgba(255,255,255,.12); overflow:hidden; box-shadow:0 8px 32px rgba(0,0,0,.2); }
    .bd-colis-coverage__map-head { background:rgba(255,255,255,.08); padding:1rem 1.4rem; border-bottom:1px solid rgba(255,255,255,.1); display:flex; align-items:center; justify-content:space-between; }
    .bd-colis-coverage__map-head strong { font-size:.78rem; font-weight:700; color:rgba(255,255,255,.5); letter-spacing:.06em; text-transform:uppercase; }
    .bd-colis-coverage__map-head span { display:flex; align-items:center; gap:5px; font-size:.68rem; color:var(--colis-secondary); font-weight:700; }
    .bd-colis-coverage__map-head span i { width:6px; height:6px; background:var(--colis-primary); border-radius:50%; animation:bdColisPulse 2s infinite; display:block; }
    .bd-colis-coverage__map-head span.is-gold { color:var(--gold-light); } .bd-colis-coverage__map-head span.is-gold i { background:var(--gold-light); }
    .bd-colis-coverage__map-head span.is-soft { color:var(--text3); } .bd-colis-coverage__map-head span.is-soft i { background:var(--text3); animation:none; }
    .bd-colis-coverage__map-body { padding:1.6rem; }
    .bd-colis-congo-svg { width:100%; height:220px; }
    .bd-colis-congo-svg text { text-anchor:middle; font-family:Manrope,sans-serif; font-size:9px; fill:rgba(255,255,255,.82); font-weight:700; letter-spacing:1px; }
    .bd-colis-congo-svg text.is-soft { fill:var(--text3); font-size:8px; letter-spacing:.5px; }
    .bd-colis-zone-ping { animation:bdColisZonePing 2s ease-in-out infinite; }
    .bd-colis-zone-ping-2 { animation:bdColisZonePing 2s ease-in-out infinite; animation-delay:.8s; }
    @keyframes bdColisZonePing { 0%,100%{r:8} 50%{r:12} }
    .bd-colis-coverage__stats { display:grid; grid-template-columns:1fr 1fr; gap:.7rem; margin-top:1.2rem; }
    .bd-colis-coverage__stats div { background:rgba(255,255,255,.07); border-radius:var(--r-m); padding:.85rem 1rem; border:1px solid rgba(255,255,255,.1); }
    .bd-colis-coverage__stats strong { display:block; font-family:'Cormorant Garamond', serif; font-size:1.3rem; font-weight:300; color:var(--colis-secondary); margin-bottom:2px; }
    .bd-colis-coverage__stats strong.is-gold { color:var(--gold-light); } .bd-colis-coverage__stats strong.is-soft { color:var(--text2); }
    .bd-colis-coverage__stats span { font-size:.66rem; color:rgba(255,255,255,.4); font-weight:600; letter-spacing:.06em; text-transform:uppercase; }
    .bd-colis-conditions { padding:7rem 0; background:linear-gradient(135deg,#007836 0%,#009543 50%,#c05808 100%); position:relative; overflow:hidden; }
    .bd-colis-conditions::before { content:''; position:absolute; inset:0; background:radial-gradient(ellipse at 20% 50%, rgba(255,200,100,.08) 0%, transparent 55%); }
    .bd-colis-conditions > .bd-colis-wrap { position:relative; z-index:1; }
    .bd-colis-conditions h2 { color:#fff; margin-bottom:3rem; } .bd-colis-conditions h2 em { color:rgba(255,255,255,.65); }
    .bd-colis-conditions__grid { display:grid; grid-template-columns:1fr 1fr; gap:1.4rem; }
    .bd-colis-conditions__card { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.18); border-radius:var(--r-xl); padding:2.4rem; backdrop-filter:blur(8px); transition:.25s; }
    .bd-colis-conditions__card:hover { background:rgba(255,255,255,.16); box-shadow:0 12px 40px rgba(0,0,0,.15); transform:translateY(-3px); }
    .bd-colis-conditions__icon { width:48px; height:48px; border-radius:var(--r-m); border:1px solid rgba(255,255,255,.2); background:rgba(255,255,255,.12); display:flex; align-items:center; justify-content:center; margin-bottom:1.4rem; color:rgba(255,255,255,.9); }
    .bd-colis-conditions__icon svg { width:22px; height:22px; }
    .bd-colis-conditions__title { font-family:'Cormorant Garamond', serif; font-size:1.2rem; font-weight:400; color:#fff; margin-bottom:1.2rem; }
    .bd-colis-conditions__list { display:flex; flex-direction:column; gap:.85rem; }
    .bd-colis-conditions__list div { display:flex; align-items:flex-start; gap:.85rem; font-size:.84rem; color:rgba(255,255,255,.7); line-height:1.65; }
    .bd-colis-conditions__list span { width:20px; height:20px; border-radius:50%; background:rgba(255,255,255,.15); display:flex; flex-shrink:0; margin-top:1px; position:relative; }
    .bd-colis-conditions__list span::after { content:''; width:8px; height:8px; border-radius:50%; background:#fff; position:absolute; inset:6px; }
    .bd-colis-links { padding:7rem 0; background:#f0f5f0; border-top:1px solid var(--border); }
    .bd-colis-links h2 span { font-style:italic; color:var(--green); }
    .bd-colis-opportunities { padding:0 0 7rem; background:#fefefe; }
    .bd-colis-opportunities__grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; margin-top:2rem; }
    .bd-colis-opportunity { background:#fff; border:1px solid var(--border); border-radius:var(--r-xl); box-shadow:0 18px 44px rgba(10,38,20,.06); overflow:hidden; display:flex; flex-direction:column; }
    .bd-colis-opportunity__media { aspect-ratio:16/9; background:#e6efe8; }
    .bd-colis-opportunity__media img { width:100%; height:100%; object-fit:cover; display:block; }
    .bd-colis-opportunity__content { padding:1.4rem; display:flex; flex-direction:column; flex:1; }
    .bd-colis-opportunity__title { font-size:1.05rem; font-weight:700; color:var(--text); margin-bottom:.75rem; }
    .bd-colis-opportunity__body { font-size:.9rem; color:var(--text2); line-height:1.75; margin-bottom:1rem; }
    .bd-colis-opportunity__cta { color:var(--green); font-weight:700; text-decoration:none; }
    .bd-colis-links__grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1.2rem; margin-top:3rem; }
    .bd-colis-links__card { background:#f8faf8; border:1px solid var(--border); border-radius:var(--r-xl); padding:2.2rem 2rem; color:inherit; text-decoration:none; transition:.25s; position:relative; overflow:hidden; }
    .bd-colis-links__card:hover { border-color:rgba(27,110,66,.2); transform:translateY(-5px); box-shadow:0 16px 40px rgba(24,26,22,.1); }
    .bd-colis-links__card::before { content:''; position:absolute; bottom:0; left:0; right:0; height:3px; background:linear-gradient(90deg,var(--green),var(--green-mid)); transform:scaleX(0); transform-origin:left; transition:transform .35s cubic-bezier(.22,1,.36,1); }
    .bd-colis-links__card:hover::before { transform:scaleX(1); }
    .bd-colis-links__icon { width:46px; height:46px; border-radius:var(--r-s); border:1px solid var(--border); background:var(--bg3); display:flex; align-items:center; justify-content:center; margin-bottom:1.3rem; transition:.2s; color:var(--green); }
    .bd-colis-links__icon svg { width:20px; height:20px; }
    .bd-colis-links__icon.is-gold { color:var(--gold); } .bd-colis-links__icon.is-blue { color:var(--blue); } .bd-colis-links__icon.is-dark { color:var(--text2); }
    .bd-colis-links__card:hover .bd-colis-links__icon { transform:scale(1.06); }
    .bd-colis-links__title { font-family:'Cormorant Garamond', serif; font-size:1.1rem; font-weight:400; color:var(--text); margin-bottom:.5rem; }
    .bd-colis-links__desc { font-size:.78rem; color:var(--text2); line-height:1.75; }
    .bd-colis-links__arrow { display:flex; align-items:center; gap:.4rem; font-size:.72rem; font-weight:700; color:var(--green); margin-top:1.2rem; transition:gap .25s; }
    .bd-colis-links__arrow svg { width:12px; height:12px; } .bd-colis-links__arrow.is-gold { color:var(--gold); } .bd-colis-links__arrow.is-blue { color:var(--blue); } .bd-colis-links__arrow.is-dark { color:var(--text2); }
    .bd-colis-links__card:hover .bd-colis-links__arrow { gap:.75rem; }
    .bd-colis-cta { padding:7rem 0; background:linear-gradient(135deg,#0a2614 0%,#134f2e 40%,#0f3d22 100%); position:relative; overflow:hidden; }
    .bd-colis-cta::before { content:''; position:absolute; inset:0; background:radial-gradient(ellipse at 90% 20%, rgba(0,149,67,.12) 0%, transparent 50%); }
    #bdColisCtaCanvas { position:absolute; inset:0; z-index:0; pointer-events:none; opacity:.2; }
    .bd-colis-cta > .bd-colis-wrap { position:relative; z-index:1; }
    .bd-colis-cta__inner { display:grid; grid-template-columns:1fr 1fr; gap:4rem; align-items:center; }
    .bd-colis-cta h2 { color:#fff; } .bd-colis-cta h2 em { color:rgba(255,255,255,.65); } .bd-colis-cta p { font-size:.9rem; color:rgba(255,255,255,.55); line-height:1.85; margin-bottom:2rem; }
    .bd-colis-cta__buttons { display:flex; gap:.9rem; flex-wrap:wrap; }
    .bd-colis-cta__status { background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.12); border-radius:var(--r-xl); padding:2rem; display:flex; flex-direction:column; gap:1rem; }
    .bd-colis-cta__status-title { font-size:.72rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:rgba(255,255,255,.4); margin-bottom:.4rem; }
    .bd-colis-cta__status-row { display:flex; align-items:center; gap:.85rem; padding:.85rem; background:rgba(255,255,255,.05); border-radius:var(--r-m); border:1px solid rgba(255,255,255,.08); }
    .bd-colis-cta__dot { width:9px; height:9px; border-radius:50%; flex-shrink:0; } .bd-colis-cta__dot.is-ok { background:#4ade80; } .bd-colis-cta__dot.is-warn { background:#fbbf24; }
    .bd-colis-cta__label { font-size:.82rem; font-weight:600; color:rgba(255,255,255,.7); } .bd-colis-cta__val { margin-left:auto; font-size:.78rem; font-weight:700; } .bd-colis-cta__val.is-ok { color:#4ade80; } .bd-colis-cta__val.is-warn { color:#fbbf24; }
    .bd-colis-cta__hotline { display:flex; align-items:center; gap:.85rem; padding:1rem 1.2rem; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); border-radius:var(--r-m); text-decoration:none; }
    .bd-colis-cta__hotline-icon { width:36px; height:36px; border-radius:var(--r-s); background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; flex-shrink:0; color:rgba(255,255,255,.8); }
    .bd-colis-cta__hotline-icon svg { width:18px; height:18px; }
    .bd-colis-cta__hotline-label { font-size:.78rem; font-weight:700; color:rgba(255,255,255,.85); } .bd-colis-cta__hotline-sub { font-size:.66rem; color:rgba(255,255,255,.45); margin-top:1px; }
    .bd-colis-footer { background:linear-gradient(160deg,#0f2018 0%,#0a1810 100%); padding:5rem 3rem 2.5rem; border-top:3px solid var(--colis-primary); }
    .bd-colis-footer__grid { display:grid; grid-template-columns:2fr 1fr 1fr 1fr; gap:3rem; padding-bottom:3.5rem; max-width:1160px; margin:0 auto; border-bottom:1px solid rgba(255,255,255,.1); }
    .bd-colis-footer__logo { display:flex; align-items:center; gap:.6rem; font-family:'Cormorant Garamond', serif; font-size:1.5rem; color:#fff; margin-bottom:1.2rem; }
    .bd-colis-footer__brand p { font-size:.79rem; color:rgba(255,255,255,.55); line-height:1.85; max-width:255px; margin-bottom:1.8rem; }
    .bd-colis-footer__socials { display:flex; gap:.7rem; }
    .bd-colis-footer__socials a { width:32px; height:32px; border-radius:50%; border:1px solid rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,.5); transition:.15s; text-decoration:none; }
    .bd-colis-footer__socials a:hover { border-color:rgba(255,255,255,.5); background:rgba(255,255,255,.1); color:#fff; }
    .bd-colis-footer__col h4 { font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.18em; color:rgba(255,255,255,.5); margin-bottom:1.3rem; }
    .bd-colis-footer__links { display:flex; flex-direction:column; gap:.6rem; }
    .bd-colis-footer__links a { font-size:.78rem; color:rgba(255,255,255,.45); text-decoration:none; transition:color .15s; }
    .bd-colis-footer__links a:hover { color:rgba(255,255,255,.85); }
    .bd-colis-footer__bottom { display:flex; justify-content:space-between; align-items:center; padding-top:2.2rem; max-width:1160px; margin:0 auto; flex-wrap:wrap; gap:.8rem; }
    .bd-colis-footer__copy { font-size:.72rem; color:rgba(255,255,255,.35); }
    .bd-colis-footer__pay { display:flex; gap:.55rem; flex-wrap:wrap; }
    .bd-colis-footer__pay span { background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); border-radius:5px; padding:3px 9px; font-size:.6rem; font-weight:700; color:rgba(255,255,255,.35); letter-spacing:.07em; text-transform:uppercase; }
    .bd-colis-footer__legal { display:flex; gap:1.5rem; flex-wrap:wrap; }
    .bd-colis-footer__legal a { font-size:.7rem; color:rgba(255,255,255,.28); text-decoration:none; transition:color .15s; }
    .bd-colis-footer__legal a:hover { color:rgba(255,255,255,.6); }
    @media (max-width: 900px) {
        #bd-colis-cursor { display:none; }
        body.bd-future-shell { cursor:auto; }
        .bd-colis-nav { padding:.8rem 1.5rem; }
        .bd-colis-wrap, .bd-colis-footer { padding-left:1.5rem; padding-right:1.5rem; }
        .bd-colis-nav__links { display:none; }
        .bd-colis-hero__inner, .bd-colis-trust__grid, .bd-colis-coverage__panel, .bd-colis-conditions__grid, .bd-colis-cta__inner, .bd-colis-footer__grid, .bd-colis-testimonials, .bd-colis-opportunities__grid { grid-template-columns:1fr; }
        .bd-colis-links__grid { grid-template-columns:1fr 1fr; }
        .bd-colis-actions__grid { grid-template-columns:1fr 1fr; }
        .bd-colis-hero__visual, .bd-colis-proof-floating--left { display:none; }
    }
    @media (max-width: 767px) {
        .bd-colis-track-bar { flex-direction:column; border-radius:24px; }
        .bd-colis-track-bar__button, .bd-colis-btn-primary, .bd-colis-btn-outline, .bd-colis-btn-red { width:100%; justify-content:center; }
        .bd-colis-links__grid, .bd-colis-actions__grid { grid-template-columns:1fr; }
        .bd-colis-footer { padding-left:1.25rem; padding-right:1.25rem; }
        .bd-colis-footer__bottom, .bd-colis-footer__legal, .bd-colis-footer__pay { justify-content:flex-start; }
        .bd-colis-hero__inner { padding:7rem 1.5rem 4rem; }
        .bd-colis-hero__title { font-size:clamp(2.8rem, 11vw, 4.2rem); }
        .bd-colis-wa-float { right:1rem; bottom:1rem; }
    }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dot = document.getElementById('bd-colis-cursor-dot');
    const ring = document.getElementById('bd-colis-cursor-ring');
    let mx = 0, my = 0, rx = 0, ry = 0;

    if (dot && ring && window.matchMedia('(min-width: 901px)').matches) {
        document.addEventListener('mousemove', function (e) {
            mx = e.clientX;
            my = e.clientY;
            dot.style.transform = 'translate(' + mx + 'px,' + my + 'px) translate(-50%,-50%)';
        });

        (function animateCursor() {
            rx += (mx - rx) * 0.18;
            ry += (my - ry) * 0.18;
            ring.style.transform = 'translate(' + rx + 'px,' + ry + 'px) translate(-50%,-50%)';
            requestAnimationFrame(animateCursor);
        }());
    }

    const nav = document.getElementById('bdColisNav');
    window.addEventListener('scroll', function () {
        if (!nav) return;
        nav.classList.toggle('is-compact', window.scrollY > 60);
    });

    const trackInput = document.getElementById('bdColisTrackInput');
    const trackResult = document.getElementById('bdColisTrackResult');
    if (trackInput && trackResult) {
        const updateTrackPreview = function () {
            const value = trackInput.value.trim();
            if (!value) {
                trackResult.style.display = 'none';
                return;
            }
            trackResult.style.display = 'block';
            trackResult.style.color = 'var(--green)';
            trackResult.textContent = 'Envoi ' + value + ' — En route · Estimation remise : 10h15 · Brazzaville';
        };
        trackInput.addEventListener('input', updateTrackPreview);
    }

    const tabs = Array.from(document.querySelectorAll('.bd-colis-coverage__tab'));
    const panels = Array.from(document.querySelectorAll('.bd-colis-coverage__panel'));
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const index = tab.getAttribute('data-tab');
            tabs.forEach(function (item) { item.classList.remove('is-active'); });
            panels.forEach(function (panel) { panel.classList.remove('is-active'); });
            tab.classList.add('is-active');
            const activePanel = document.querySelector('.bd-colis-coverage__panel[data-panel="' + index + '"]');
            if (activePanel) activePanel.classList.add('is-active');
        });
    });

    const heroCanvas = document.getElementById('bdColisHeroCanvas');
    if (heroCanvas) {
        const heroCtx = heroCanvas.getContext('2d');
        let particles = [];
        const resizeHero = function () {
            heroCanvas.width = window.innerWidth;
            heroCanvas.height = window.innerHeight;
            particles = [];
            for (let i = 0; i < 30; i += 1) {
                particles.push({
                    x: Math.random() * heroCanvas.width,
                    y: Math.random() * heroCanvas.height,
                    r: Math.random() * 1.3 + 0.3,
                    vx: (Math.random() - 0.5) * 0.16,
                    vy: (Math.random() - 0.5) * 0.16,
                    a: Math.random() * 0.08 + 0.02,
                    c: Math.random() > 0.6 ? '45,186,110' : '212,150,12'
                });
            }
        };
        const drawHero = function () {
            heroCtx.clearRect(0, 0, heroCanvas.width, heroCanvas.height);
            particles.forEach(function (p) {
                p.x += p.vx;
                p.y += p.vy;
                if (p.x < 0) p.x = heroCanvas.width;
                if (p.x > heroCanvas.width) p.x = 0;
                if (p.y < 0) p.y = heroCanvas.height;
                if (p.y > heroCanvas.height) p.y = 0;
                heroCtx.beginPath();
                heroCtx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                heroCtx.fillStyle = 'rgba(' + p.c + ',' + p.a + ')';
                heroCtx.fill();
            });
            for (let i = 0; i < particles.length; i += 1) {
                for (let j = i + 1; j < particles.length; j += 1) {
                    const d = Math.hypot(particles[i].x - particles[j].x, particles[i].y - particles[j].y);
                    if (d < 80) {
                        heroCtx.beginPath();
                        heroCtx.moveTo(particles[i].x, particles[i].y);
                        heroCtx.lineTo(particles[j].x, particles[j].y);
                        heroCtx.strokeStyle = 'rgba(45,186,110,' + (0.035 * (1 - d / 80)) + ')';
                        heroCtx.lineWidth = 0.4;
                        heroCtx.stroke();
                    }
                }
            }
            requestAnimationFrame(drawHero);
        };
        resizeHero();
        drawHero();
        window.addEventListener('resize', resizeHero);
    }

    const ctaCanvas = document.getElementById('bdColisCtaCanvas');
    if (ctaCanvas) {
        const ctaCtx = ctaCanvas.getContext('2d');
        let drops = [];
        const resizeCta = function () {
            ctaCanvas.width = ctaCanvas.parentElement.offsetWidth;
            ctaCanvas.height = ctaCanvas.parentElement.offsetHeight;
            drops = [];
            for (let i = 0; i < 70; i += 1) {
                drops.push({
                    x: Math.random() * ctaCanvas.width,
                    y: Math.random() * ctaCanvas.height,
                    l: Math.random() * 12 + 4,
                    s: Math.random() * 2.5 + 1,
                    a: Math.random() * 0.07 + 0.02,
                    t: Math.random() * 0.5 + 0.2
                });
            }
        };
        const drawCta = function () {
            ctaCtx.clearRect(0, 0, ctaCanvas.width, ctaCanvas.height);
            drops.forEach(function (d) {
                ctaCtx.beginPath();
                ctaCtx.moveTo(d.x, d.y);
                ctaCtx.lineTo(d.x - 0.8, d.y + d.l);
                ctaCtx.strokeStyle = 'rgba(45,186,110,' + d.a + ')';
                ctaCtx.lineWidth = d.t;
                ctaCtx.stroke();
                d.y += d.s;
                if (d.y > ctaCanvas.height) {
                    d.y = -d.l;
                    d.x = Math.random() * ctaCanvas.width;
                }
            });
            requestAnimationFrame(drawCta);
        };
        resizeCta();
        drawCta();
        window.addEventListener('resize', resizeCta);
    }
});
</script>
@endsection
