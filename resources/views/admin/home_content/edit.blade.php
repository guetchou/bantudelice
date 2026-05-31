@extends('layouts.admin-modern')
@section('title', 'Accueil — Contenu')
@section('page_title', 'Éditeur d\'accueil')
@section('nav_active', 'cms')

@section('content')
@php
    $cmsWorkspace = $cmsWorkspace ?? [
        'key' => request('workspace', 'bantudelice'),
        'label' => request('workspace') === 'kende' ? 'Kende' : (request('workspace') === 'mema' ? 'Mema' : 'BantuDelice'),
        'eyebrow' => request('workspace') === 'kende' ? 'Accueil transport' : (request('workspace') === 'mema' ? 'Accueil colis' : 'Accueil food'),
        'description' => request('workspace') === 'kende'
            ? 'Pilotez le hero, les sections trajets, flotte et conversion transport.'
            : (request('workspace') === 'mema'
                ? 'Pilotez le hero, les sections logistiques, relais et parcours colis.'
                : 'Pilotez le hero, les sections restaurants, plats et storefront food.'),
    ];
    $homeContentWorkspace = $cmsWorkspace['key'];
    $homePreviewUrl = $homeContentWorkspace === 'kende'
        ? route('transport.taxi')
        : ($homeContentWorkspace === 'mema' ? route('colis.landing') : route('home'));
    $content = $content ?? [];
    $showFoodSections = $homeContentWorkspace === 'bantudelice';
    $heroMediaFields = match ($homeContentWorkspace) {
        'kende' => [[
            'input' => 'home_hero_transport_image',
            'select' => 'home_hero_transport_image_media_path',
            'label' => 'Image hero transport',
            'value' => $content['hero_transport_image'] ?? null,
            'preview_target' => 'hero-transport-image',
        ]],
        'mema' => [[
            'input' => 'home_hero_colis_image',
            'select' => 'home_hero_colis_image_media_path',
            'label' => 'Image hero colis',
            'value' => $content['hero_colis_image'] ?? null,
            'preview_target' => 'hero-colis-image',
        ]],
        default => [
            [
                'input' => 'home_hero_main_image',
                'select' => 'home_hero_main_image_media_path',
                'label' => 'Image principale',
                'value' => $content['hero_main_image'] ?? null,
                'preview_target' => 'hero-main-image',
            ],
            [
                'input' => 'home_hero_colis_image',
                'select' => 'home_hero_colis_image_media_path',
                'label' => 'Image colis',
                'value' => $content['hero_colis_image'] ?? null,
                'preview_target' => 'hero-colis-image',
            ],
            [
                'input' => 'home_hero_transport_image',
                'select' => 'home_hero_transport_image_media_path',
                'label' => 'Image transport',
                'value' => $content['hero_transport_image'] ?? null,
                'preview_target' => 'hero-transport-image',
            ],
        ],
    };
    $testimonialDefaults = [
        1 => ['tag' => 'Livraison repas', 'quote' => 'La commande arrive chaude, proprement emballee et dans les delais annonces.', 'name' => 'Prisca M.', 'loc' => 'Centre-ville, Brazzaville'],
        2 => ['tag' => 'Service colis', 'quote' => 'Le suivi est clair et la prise en charge rassurante pour les envois du quotidien.', 'name' => 'Cedric N.', 'loc' => 'Littoral congolais'],
        3 => ['tag' => 'Transport', 'quote' => 'Tarif affiche avant confirmation et reservation simple depuis le telephone.', 'name' => 'Aimee K.', 'loc' => 'Bacongo, Brazzaville'],
    ];
    $opportunityDefaults = [
        1 => ['title' => 'Devenir coursier', 'body' => "Rejoignez le reseau de la plateforme pour livrer repas et colis.", 'cta' => 'Inscription', 'url' => route('driver'), 'image' => 'images/home/service-driver.jpg'],
        2 => ['title' => 'Devenir partenaire', 'body' => 'Restaurants, commerces et enseignes peuvent developper leur visibilite.', 'cta' => 'Inscription', 'url' => route('partner'), 'image' => 'images/home/service-restaurant.jpg'],
        3 => ['title' => 'Emploi', 'body' => "Rejoignez l'equipe plateforme ou proposez votre profil.", 'cta' => 'Nous contacter', 'url' => route('contact.us'), 'image' => 'images/home/service-transport.jpg'],
    ];
    $testimonialPreview = collect([1,2,3])->mapWithKeys(fn($i) => [$i => [
        'tag'   => old("home_testimonial_{$i}_tag",   $content["testimonial_{$i}_tag"]   ?? $testimonialDefaults[$i]['tag']),
        'quote' => old("home_testimonial_{$i}_quote", $content["testimonial_{$i}_quote"] ?? $testimonialDefaults[$i]['quote']),
        'name'  => old("home_testimonial_{$i}_name",  $content["testimonial_{$i}_name"]  ?? $testimonialDefaults[$i]['name']),
        'loc'   => old("home_testimonial_{$i}_loc",   $content["testimonial_{$i}_loc"]   ?? $testimonialDefaults[$i]['loc']),
    ]])->all();
    $opportunityPreview = collect([1,2,3])->mapWithKeys(fn($i) => [$i => [
        'title' => old("home_opportunity_{$i}_title", $content["opportunity_{$i}_title"] ?? $opportunityDefaults[$i]['title']),
        'body'  => old("home_opportunity_{$i}_body",  $content["opportunity_{$i}_body"]  ?? $opportunityDefaults[$i]['body']),
        'cta'   => old("home_opportunity_{$i}_cta",   $content["opportunity_{$i}_cta"]   ?? $opportunityDefaults[$i]['cta']),
        'url'   => old("home_opportunity_{$i}_url",   $content["opportunity_{$i}_url"]   ?? $opportunityDefaults[$i]['url']),
        'image' => asset($content["opportunity_{$i}_image"] ?? $opportunityDefaults[$i]['image']),
    ]])->all();
    $mediaSlotKeys = ['hero_main_image','hero_colis_image','hero_transport_image','service_food_image','service_colis_image','service_transport_image','opportunity_1_image','opportunity_2_image','opportunity_3_image'];
    $filledMediaCount = collect($mediaSlotKeys)->filter(fn($k) => filled((string)($content[$k] ?? '')))->count();
    $mediaSlotCount = count($heroMediaFields) + ($showFoodSections ? 3 : 0) + 3;
    $tabs = collect([
        ['id' => 'hero',        'label' => 'Hero'],
        ['id' => 'medias',      'label' => 'Médias'],
        $showFoodSections ? ['id' => 'editorial',    'label' => 'Éditorial'] : null,
        ['id' => 'temoignages', 'label' => 'Témoignages'],
        ['id' => 'opportunites','label' => 'Opportunités'],
        ['id' => 'support',     'label' => 'Support'],
    ])->filter()->values();
@endphp

<div class="pfe-root">

    {{-- ── TOP BAR ─────────────────────────────────────────────────────────── --}}
    <div class="pfe-topbar">
        <div class="pfe-topbar__left">
            <nav class="pfe-breadcrumb">
                <a href="{{ route('admin.cms.dashboard') }}">CMS</a>
                <span>/</span>
                <span>{{ $cmsWorkspace['label'] }}</span>
                <span>/</span>
                <span>Accueil</span>
            </nav>
            <h1 class="pfe-title">Contenu de l'accueil <span>{{ $cmsWorkspace['label'] }}</span></h1>
        </div>
        <div class="pfe-topbar__right">
            <span class="pfe-media-badge {{ $filledMediaCount >= $mediaSlotCount ? 'pfe-media-badge--ok' : 'pfe-media-badge--warn' }}">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/><polyline points="8 12 11 15 16 9"/></svg>
                {{ $filledMediaCount }}/{{ $mediaSlotCount }} médias
            </span>
            <a href="{{ $homePreviewUrl }}" target="_blank" rel="noopener" class="pfe-btn pfe-btn--ghost">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                Aperçu
            </a>
            <button form="pfe-form" type="submit" class="pfe-btn pfe-btn--primary">Enregistrer</button>
        </div>
    </div>

    {{-- ── ALERT FLASH ─────────────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="pfe-flash pfe-flash--ok">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="pfe-flash pfe-flash--err">{{ $errors->first() }}</div>
    @endif

    {{-- ── TAB BAR ─────────────────────────────────────────────────────────── --}}
    <div class="pfe-tabbar" role="tablist">
        @foreach($tabs as $tab)
            <button class="pfe-tab" role="tab" data-tab="{{ $tab['id'] }}" aria-selected="false">
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    {{-- ── FORM ────────────────────────────────────────────────────────────── --}}
    <form id="pfe-form"
          method="POST"
          action="{{ route('admin.home-content.update', ['workspace' => $homeContentWorkspace]) }}"
          enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- ╔══════════════════════════════════════════════════════════════╗ --}}
        {{-- ║  PANEL — HERO                                                ║ --}}
        {{-- ╚══════════════════════════════════════════════════════════════╝ --}}
        <div class="pfe-panel" data-panel="hero">
            <div class="pfe-panel__body">
                <div class="pfe-section">
                    <h2 class="pfe-section__title">Textes du hero</h2>
                    <div class="pfe-grid pfe-grid--2">
                        <div class="pfe-field">
                            <label class="pfe-label">Badge</label>
                            <input type="text" name="home_hero_badge" class="pfe-input"
                                   value="{{ old('home_hero_badge', $content['hero_badge'] ?? '') }}"
                                   placeholder="ex. Livraison rapide à Brazzaville">
                        </div>
                        <div class="pfe-field">
                            <label class="pfe-label">Description courte</label>
                            <textarea name="home_hero_description" rows="3" class="pfe-input"
                                      placeholder="Texte sous le titre principal">{{ old('home_hero_description', $content['hero_description'] ?? '') }}</textarea>
                        </div>
                        <div class="pfe-field">
                            <label class="pfe-label">Titre — ligne 1</label>
                            <input type="text" name="home_hero_title_line_1" class="pfe-input pfe-input--lg"
                                   value="{{ old('home_hero_title_line_1', $content['hero_title_line_1'] ?? '') }}">
                        </div>
                        <div class="pfe-field">
                            <label class="pfe-label">Titre — ligne 2</label>
                            <input type="text" name="home_hero_title_line_2" class="pfe-input pfe-input--lg"
                                   value="{{ old('home_hero_title_line_2', $content['hero_title_line_2'] ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ╔══════════════════════════════════════════════════════════════╗ --}}
        {{-- ║  PANEL — MÉDIAS                                              ║ --}}
        {{-- ╚══════════════════════════════════════════════════════════════╝ --}}
        <div class="pfe-panel" data-panel="medias">
            <div class="pfe-panel__body">

                <div class="pfe-section">
                    <h2 class="pfe-section__title">Visuels hero <span class="pfe-section__count">{{ count($heroMediaFields) }} image{{ count($heroMediaFields) > 1 ? 's' : '' }}</span></h2>
                    <div class="pfe-grid pfe-grid--3">
                        @foreach($heroMediaFields as $field)
                            <div class="pfe-field">
                                <label class="pfe-label">{{ $field['label'] }}</label>
                                @if(!empty($field['value']))
                                    <div class="pfe-thumb-wrap">
                                        <img src="{{ asset($field['value']) }}" alt="" class="pfe-thumb" data-preview-node="{{ $field['preview_target'] }}">
                                    </div>
                                @endif
                                <input type="file" name="{{ $field['input'] }}" class="pfe-file" data-preview-image="{{ $field['preview_target'] }}" accept="image/*">
                                @include('partials.unified_media_select', [
                                    'name'          => $field['select'],
                                    'label'         => 'Choisir dans la médiathèque',
                                    'options'       => $mediaLibraryOptions ?? [],
                                    'selected'      => $field['value'],
                                    'previewTarget' => $field['preview_target'],
                                ])
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($showFoodSections)
                <div class="pfe-section">
                    <h2 class="pfe-section__title">Images des services <span class="pfe-section__count">3 images</span></h2>
                    <div class="pfe-grid pfe-grid--3">
                        @foreach([
                            ['input'=>'home_service_food_image',      'select'=>'home_service_food_image_media_path',      'label'=>'Repas',      'key'=>'service_food_image',      'target'=>'service-food-image'],
                            ['input'=>'home_service_colis_image',     'select'=>'home_service_colis_image_media_path',     'label'=>'Colis',      'key'=>'service_colis_image',     'target'=>'service-colis-image'],
                            ['input'=>'home_service_transport_image', 'select'=>'home_service_transport_image_media_path', 'label'=>'Transport',  'key'=>'service_transport_image', 'target'=>'service-transport-image'],
                        ] as $f)
                            <div class="pfe-field">
                                <label class="pfe-label">{{ $f['label'] }}</label>
                                @if(!empty($content[$f['key']]))
                                    <div class="pfe-thumb-wrap">
                                        <img src="{{ asset($content[$f['key']]) }}" alt="" class="pfe-thumb" data-preview-node="{{ $f['target'] }}">
                                    </div>
                                @endif
                                <input type="file" name="{{ $f['input'] }}" class="pfe-file" data-preview-image="{{ $f['target'] }}" accept="image/*">
                                @include('partials.unified_media_select', ['name'=>$f['select'],'label'=>'Médiathèque','options'=>$mediaLibraryOptions??[],'selected'=>$content[$f['key']]??'','previewTarget'=>$f['target']])
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="pfe-section">
                    <h2 class="pfe-section__title">Mosaïque livraison <span class="pfe-section__count">3 images</span></h2>
                    <div class="pfe-grid pfe-grid--3">
                        @foreach([
                            ['input'=>'home_mosaic_cuisine_image',    'select'=>'home_mosaic_cuisine_image_media_path',    'label'=>'Cuisine',    'key'=>'mosaic_cuisine_image',    'target'=>'mosaic-cuisine-image'],
                            ['input'=>'home_mosaic_driver_image',     'select'=>'home_mosaic_driver_image_media_path',     'label'=>'Livreur',    'key'=>'mosaic_driver_image',     'target'=>'mosaic-driver-image'],
                            ['input'=>'home_mosaic_restaurant_image', 'select'=>'home_mosaic_restaurant_image_media_path', 'label'=>'Restaurant', 'key'=>'mosaic_restaurant_image', 'target'=>'mosaic-restaurant-image'],
                        ] as $f)
                            <div class="pfe-field">
                                <label class="pfe-label">{{ $f['label'] }}</label>
                                @if(!empty($content[$f['key']]))
                                    <div class="pfe-thumb-wrap">
                                        <img src="{{ asset($content[$f['key']]) }}" alt="" class="pfe-thumb" data-preview-node="{{ $f['target'] }}">
                                    </div>
                                @endif
                                <input type="file" name="{{ $f['input'] }}" class="pfe-file" data-preview-image="{{ $f['target'] }}" accept="image/*">
                                @include('partials.unified_media_select', ['name'=>$f['select'],'label'=>'Médiathèque','options'=>$mediaLibraryOptions??[],'selected'=>$content[$f['key']]??'','previewTarget'=>$f['target']])
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>

        {{-- ╔══════════════════════════════════════════════════════════════╗ --}}
        {{-- ║  PANEL — ÉDITORIAL (food uniquement)                        ║ --}}
        {{-- ╚══════════════════════════════════════════════════════════════╝ --}}
        @if($showFoodSections)
        <div class="pfe-panel" data-panel="editorial">
            <div class="pfe-panel__body">

                <div class="pfe-section">
                    <h2 class="pfe-section__title">Restaurants populaires</h2>
                    <div class="pfe-grid pfe-grid--3">
                        <div class="pfe-field">
                            <label class="pfe-label">Tag</label>
                            <input type="text" name="home_restaurants_tag" class="pfe-input"
                                   value="{{ old('home_restaurants_tag', $content['restaurants_tag'] ?? 'Partenaires sélectionnés') }}">
                        </div>
                        <div class="pfe-field">
                            <label class="pfe-label">Titre</label>
                            <input type="text" name="home_restaurants_title" class="pfe-input"
                                   value="{{ old('home_restaurants_title', $content['restaurants_title'] ?? 'Restaurants populaires') }}">
                        </div>
                        <div class="pfe-field">
                            <label class="pfe-label">Sous-titre</label>
                            <input type="text" name="home_restaurants_subtitle" class="pfe-input"
                                   value="{{ old('home_restaurants_subtitle', $content['restaurants_subtitle'] ?? '') }}">
                        </div>
                    </div>
                </div>

                <div class="pfe-section">
                    <h2 class="pfe-section__title">Plats populaires</h2>
                    <div class="pfe-grid pfe-grid--3">
                        <div class="pfe-field">
                            <label class="pfe-label">Tag</label>
                            <input type="text" name="home_popular_products_tag" class="pfe-input"
                                   value="{{ old('home_popular_products_tag', $content['popular_products_tag'] ?? 'Sélection du moment') }}">
                        </div>
                        <div class="pfe-field">
                            <label class="pfe-label">Titre</label>
                            <input type="text" name="home_popular_products_title" class="pfe-input"
                                   value="{{ old('home_popular_products_title', $content['popular_products_title'] ?? 'Plats à découvrir') }}">
                        </div>
                        <div class="pfe-field">
                            <label class="pfe-label">Sous-titre</label>
                            <input type="text" name="home_popular_products_subtitle" class="pfe-input"
                                   value="{{ old('home_popular_products_subtitle', $content['popular_products_subtitle'] ?? '') }}">
                        </div>
                    </div>
                </div>

                <div class="pfe-section">
                    <h2 class="pfe-section__title">Trois services — titres</h2>
                    <div class="pfe-grid pfe-grid--2">
                        <div class="pfe-field">
                            <label class="pfe-label">Titre</label>
                            <input type="text" name="home_services_title" class="pfe-input"
                                   value="{{ old('home_services_title', $content['services_title'] ?? '') }}">
                        </div>
                        <div class="pfe-field">
                            <label class="pfe-label">Sous-titre</label>
                            <textarea name="home_services_subtitle" rows="3" class="pfe-input">{{ old('home_services_subtitle', $content['services_subtitle'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        @endif

        {{-- ╔══════════════════════════════════════════════════════════════╗ --}}
        {{-- ║  PANEL — TÉMOIGNAGES                                         ║ --}}
        {{-- ╚══════════════════════════════════════════════════════════════╝ --}}
        <div class="pfe-panel" data-panel="temoignages">
            <div class="pfe-panel__body pfe-panel__body--split">

                <div class="pfe-panel__form">
                    <div class="pfe-section">
                        <h2 class="pfe-section__title">En-tête de section</h2>
                        <div class="pfe-grid pfe-grid--3">
                            <div class="pfe-field">
                                <label class="pfe-label">Tag</label>
                                <input type="text" name="home_testimonials_tag" class="pfe-input"
                                       value="{{ old('home_testimonials_tag', $content['testimonials_tag'] ?? 'Avis clients') }}">
                            </div>
                            <div class="pfe-field">
                                <label class="pfe-label">Titre</label>
                                <input type="text" name="home_testimonials_title" class="pfe-input"
                                       value="{{ old('home_testimonials_title', $content['testimonials_title'] ?? 'Une confiance qui se construit') }}">
                            </div>
                            <div class="pfe-field">
                                <label class="pfe-label">Sous-titre</label>
                                <input type="text" name="home_testimonials_subtitle" class="pfe-input"
                                       value="{{ old('home_testimonials_subtitle', $content['testimonials_subtitle'] ?? '') }}">
                            </div>
                        </div>
                    </div>

                    @foreach([1,2,3] as $index)
                    <div class="pfe-section">
                        <h2 class="pfe-section__title">Avis {{ $index }}</h2>
                        <div class="pfe-grid pfe-grid--2">
                            <div class="pfe-field">
                                <label class="pfe-label">Tag</label>
                                <input type="text" name="home_testimonial_{{ $index }}_tag" class="pfe-input"
                                       data-preview-target="testimonial-{{ $index }}-tag"
                                       value="{{ old('home_testimonial_'.$index.'_tag', $content['testimonial_'.$index.'_tag'] ?? $testimonialDefaults[$index]['tag']) }}">
                            </div>
                            <div class="pfe-field">
                                <label class="pfe-label">Nom</label>
                                <input type="text" name="home_testimonial_{{ $index }}_name" class="pfe-input"
                                       data-preview-target="testimonial-{{ $index }}-name"
                                       value="{{ old('home_testimonial_'.$index.'_name', $content['testimonial_'.$index.'_name'] ?? $testimonialDefaults[$index]['name']) }}">
                            </div>
                            <div class="pfe-field pfe-field--span2">
                                <label class="pfe-label">Avis</label>
                                <textarea name="home_testimonial_{{ $index }}_quote" rows="3" class="pfe-input"
                                          data-preview-target="testimonial-{{ $index }}-quote">{{ old('home_testimonial_'.$index.'_quote', $content['testimonial_'.$index.'_quote'] ?? $testimonialDefaults[$index]['quote']) }}</textarea>
                            </div>
                            <div class="pfe-field">
                                <label class="pfe-label">Localisation</label>
                                <input type="text" name="home_testimonial_{{ $index }}_loc" class="pfe-input"
                                       data-preview-target="testimonial-{{ $index }}-loc"
                                       value="{{ old('home_testimonial_'.$index.'_loc', $content['testimonial_'.$index.'_loc'] ?? $testimonialDefaults[$index]['loc']) }}">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <aside class="pfe-panel__preview">
                    <p class="pfe-preview__label">Aperçu en direct</p>
                    @foreach([1,2,3] as $index)
                    <div class="pfe-preview-card">
                        <span class="pfe-preview-card__tag" data-preview-node="testimonial-{{ $index }}-tag">{{ $testimonialPreview[$index]['tag'] }}</span>
                        <p class="pfe-preview-card__quote" data-preview-node="testimonial-{{ $index }}-quote">{{ $testimonialPreview[$index]['quote'] }}</p>
                        <strong class="pfe-preview-card__name" data-preview-node="testimonial-{{ $index }}-name">{{ $testimonialPreview[$index]['name'] }}</strong>
                        <span class="pfe-preview-card__loc" data-preview-node="testimonial-{{ $index }}-loc">{{ $testimonialPreview[$index]['loc'] }}</span>
                    </div>
                    @endforeach
                </aside>

            </div>
        </div>

        {{-- ╔══════════════════════════════════════════════════════════════╗ --}}
        {{-- ║  PANEL — OPPORTUNITÉS                                        ║ --}}
        {{-- ╚══════════════════════════════════════════════════════════════╝ --}}
        <div class="pfe-panel" data-panel="opportunites">
            <div class="pfe-panel__body pfe-panel__body--split">

                <div class="pfe-panel__form">
                    <div class="pfe-section">
                        <h2 class="pfe-section__title">En-tête de section</h2>
                        <div class="pfe-grid pfe-grid--3">
                            <div class="pfe-field">
                                <label class="pfe-label">Tag</label>
                                <input type="text" name="home_opportunities_tag" class="pfe-input"
                                       value="{{ old('home_opportunities_tag', $content['opportunities_tag'] ?? 'Opportunités') }}">
                            </div>
                            <div class="pfe-field">
                                <label class="pfe-label">Titre</label>
                                <input type="text" name="home_opportunities_title" class="pfe-input"
                                       value="{{ old('home_opportunities_title', $content['opportunities_title'] ?? 'Grandissez avec la plateforme') }}">
                            </div>
                            <div class="pfe-field">
                                <label class="pfe-label">Sous-titre</label>
                                <textarea name="home_opportunities_subtitle" rows="2" class="pfe-input">{{ old('home_opportunities_subtitle', $content['opportunities_subtitle'] ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    @foreach([1,2,3] as $index)
                    <div class="pfe-section">
                        <h2 class="pfe-section__title">Carte {{ $index }}</h2>
                        <div class="pfe-grid pfe-grid--2">
                            <div class="pfe-field">
                                <label class="pfe-label">Titre</label>
                                <input type="text" name="home_opportunity_{{ $index }}_title" class="pfe-input"
                                       data-preview-target="opportunity-{{ $index }}-title"
                                       value="{{ old('home_opportunity_'.$index.'_title', $content['opportunity_'.$index.'_title'] ?? $opportunityDefaults[$index]['title']) }}">
                            </div>
                            <div class="pfe-field">
                                <label class="pfe-label">Label bouton</label>
                                <input type="text" name="home_opportunity_{{ $index }}_cta" class="pfe-input"
                                       data-preview-target="opportunity-{{ $index }}-cta"
                                       value="{{ old('home_opportunity_'.$index.'_cta', $content['opportunity_'.$index.'_cta'] ?? $opportunityDefaults[$index]['cta']) }}">
                            </div>
                            <div class="pfe-field pfe-field--span2">
                                <label class="pfe-label">Texte</label>
                                <textarea name="home_opportunity_{{ $index }}_body" rows="3" class="pfe-input"
                                          data-preview-target="opportunity-{{ $index }}-body">{{ old('home_opportunity_'.$index.'_body', $content['opportunity_'.$index.'_body'] ?? $opportunityDefaults[$index]['body']) }}</textarea>
                            </div>
                            <div class="pfe-field">
                                <label class="pfe-label">URL bouton</label>
                                <input type="text" name="home_opportunity_{{ $index }}_url" class="pfe-input"
                                       data-preview-target="opportunity-{{ $index }}-url"
                                       value="{{ old('home_opportunity_'.$index.'_url', $content['opportunity_'.$index.'_url'] ?? $opportunityDefaults[$index]['url']) }}">
                            </div>
                            <div class="pfe-field">
                                <label class="pfe-label">Image</label>
                                @if(!empty($content['opportunity_'.$index.'_image']) || !empty($opportunityDefaults[$index]['image']))
                                    <div class="pfe-thumb-wrap">
                                        <img src="{{ asset($content['opportunity_'.$index.'_image'] ?? $opportunityDefaults[$index]['image']) }}" alt="" class="pfe-thumb" data-preview-node="opportunity-{{ $index }}-image">
                                    </div>
                                @endif
                                <input type="file" name="home_opportunity_{{ $index }}_image" class="pfe-file" data-preview-image="opportunity-{{ $index }}-image" accept="image/*">
                                @include('partials.unified_media_select', [
                                    'name'=>'home_opportunity_'.$index.'_image_media_path',
                                    'label'=>'Médiathèque',
                                    'options'=>$mediaLibraryOptions??[],
                                    'selected'=>$content['opportunity_'.$index.'_image']??'',
                                    'previewTarget'=>'opportunity-'.$index.'-image',
                                ])
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <aside class="pfe-panel__preview">
                    <p class="pfe-preview__label">Aperçu en direct</p>
                    @foreach([1,2,3] as $index)
                    <div class="pfe-preview-opp">
                        <div class="pfe-preview-opp__img">
                            <img src="{{ $opportunityPreview[$index]['image'] }}" alt="" data-preview-node="opportunity-{{ $index }}-image">
                        </div>
                        <div class="pfe-preview-opp__body">
                            <strong data-preview-node="opportunity-{{ $index }}-title">{{ $opportunityPreview[$index]['title'] }}</strong>
                            <p data-preview-node="opportunity-{{ $index }}-body">{{ $opportunityPreview[$index]['body'] }}</p>
                            <div class="pfe-preview-opp__foot">
                                <span class="pfe-preview-opp__cta" data-preview-node="opportunity-{{ $index }}-cta">{{ $opportunityPreview[$index]['cta'] }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </aside>

            </div>
        </div>

        {{-- ╔══════════════════════════════════════════════════════════════╗ --}}
        {{-- ║  PANEL — SUPPORT                                             ║ --}}
        {{-- ╚══════════════════════════════════════════════════════════════╝ --}}
        <div class="pfe-panel" data-panel="support">
            <div class="pfe-panel__body">
                <div class="pfe-section">
                    <h2 class="pfe-section__title">Bloc d'accompagnement</h2>
                    <div class="pfe-grid pfe-grid--3">
                        <div class="pfe-field">
                            <label class="pfe-label">Titre</label>
                            <input type="text" name="home_support_title" class="pfe-input"
                                   value="{{ old('home_support_title', $content['support_title'] ?? '') }}">
                        </div>
                        <div class="pfe-field">
                            <label class="pfe-label">Description</label>
                            <textarea name="home_support_description" rows="3" class="pfe-input">{{ old('home_support_description', $content['support_description'] ?? '') }}</textarea>
                        </div>
                        <div class="pfe-field">
                            <label class="pfe-label">Bouton</label>
                            <input type="text" name="home_support_cta_text" class="pfe-input"
                                   value="{{ old('home_support_cta_text', $content['support_cta_text'] ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>

<style>
/* ── Reset scoped ── */
.pfe-root *,
.pfe-root *::before,
.pfe-root *::after { box-sizing: border-box; }

/* ── Root ── */
.pfe-root {
    display: flex;
    flex-direction: column;
    gap: 0;
    min-height: calc(100vh - 64px);
    background: #f1f3f6;
    font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', sans-serif;
}

/* ── Top bar ── */
.pfe-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 28px;
    background: #fff;
    border-bottom: 1px solid #e3e6ec;
    position: sticky;
    top: 0;
    z-index: 40;
}
.pfe-topbar__left { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.pfe-topbar__right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

.pfe-breadcrumb {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 500;
    color: #94a3b8;
}
.pfe-breadcrumb a { color: #64748b; text-decoration: none; }
.pfe-breadcrumb a:hover { color: #009543; }
.pfe-breadcrumb span { color: #94a3b8; }

.pfe-title {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pfe-title span { color: #009543; }

/* ── Buttons ── */
.pfe-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 34px;
    padding: 0 14px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: background .15s, color .15s, border-color .15s;
    text-decoration: none;
    white-space: nowrap;
}
.pfe-btn--ghost {
    background: transparent;
    border: 1px solid #d1d9e0;
    color: #475569;
}
.pfe-btn--ghost:hover { border-color: #009543; color: #009543; background: #f0fdf6; }
.pfe-btn--primary {
    background: #009543;
    color: #fff;
}
.pfe-btn--primary:hover { background: #007a38; }

/* ── Media badge ── */
.pfe-media-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 700;
}
.pfe-media-badge--ok  { background: #dcfce7; color: #166534; }
.pfe-media-badge--warn { background: #fef9c3; color: #854d0e; }

/* ── Flash ── */
.pfe-flash {
    padding: 12px 28px;
    font-size: 13px;
    font-weight: 600;
    border-bottom: 1px solid transparent;
}
.pfe-flash--ok  { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
.pfe-flash--err { background: #fef2f2; color: #991b1b; border-color: #fecaca; }

/* ── Tab bar ── */
.pfe-tabbar {
    display: flex;
    align-items: center;
    gap: 2px;
    padding: 0 28px;
    background: #fff;
    border-bottom: 1px solid #e3e6ec;
    overflow-x: auto;
    scrollbar-width: none;
}
.pfe-tabbar::-webkit-scrollbar { display: none; }

.pfe-tab {
    position: relative;
    display: inline-flex;
    align-items: center;
    height: 42px;
    padding: 0 16px;
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: color .15s;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
}
.pfe-tab:hover { color: #0f172a; }
.pfe-tab.is-active {
    color: #009543;
    border-bottom-color: #009543;
}

/* ── Panel (content area) ── */
.pfe-panel { display: none; }
.pfe-panel.is-active { display: block; }

.pfe-panel__body {
    max-width: 1100px;
    margin: 24px auto;
    padding: 0 28px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.pfe-panel__body--split {
    flex-direction: row;
    align-items: flex-start;
    gap: 24px;
}
.pfe-panel__form { flex: 1 1 0; min-width: 0; display: flex; flex-direction: column; gap: 12px; }
.pfe-panel__preview { flex: 0 0 280px; position: sticky; top: 110px; }

/* ── Section card ── */
.pfe-section {
    background: #fff;
    border: 1px solid #e3e6ec;
    border-radius: 10px;
    padding: 20px 22px;
}
.pfe-section__title {
    margin: 0 0 16px;
    font-size: 11px;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .1em;
    display: flex;
    align-items: center;
    gap: 8px;
}
.pfe-section__count {
    font-weight: 600;
    color: #cbd5e1;
    font-size: 10px;
}

/* ── Grid ── */
.pfe-grid { display: grid; gap: 14px; }
.pfe-grid--2 { grid-template-columns: repeat(2, minmax(0,1fr)); }
.pfe-grid--3 { grid-template-columns: repeat(3, minmax(0,1fr)); }

/* ── Field ── */
.pfe-field { display: flex; flex-direction: column; gap: 5px; }
.pfe-field--span2 { grid-column: span 2; }

.pfe-label {
    font-size: 11.5px;
    font-weight: 700;
    color: #475569;
    letter-spacing: .01em;
}

/* ── Input ── */
.pfe-input {
    width: 100%;
    padding: 8px 11px;
    background: #fff;
    border: 1px solid #d1d9e0;
    border-radius: 7px;
    font-size: 13.5px;
    color: #0f172a;
    line-height: 1.5;
    resize: vertical;
    transition: border-color .15s, box-shadow .15s;
    font-family: inherit;
}
.pfe-input:focus {
    outline: none;
    border-color: #009543;
    box-shadow: 0 0 0 3px rgba(0,149,67,.12);
}
.pfe-input--lg { font-size: 15px; font-weight: 600; }
.pfe-input::placeholder { color: #94a3b8; font-weight: 400; }

/* ── File input ── */
.pfe-file {
    display: block;
    width: 100%;
    padding: 7px 10px;
    background: #f8fafc;
    border: 1px dashed #d1d9e0;
    border-radius: 7px;
    font-size: 12px;
    color: #64748b;
    cursor: pointer;
    transition: border-color .15s;
}
.pfe-file:hover { border-color: #009543; }

/* ── Thumbnail ── */
.pfe-thumb-wrap {
    width: 100%;
    aspect-ratio: 16/9;
    background: #f1f3f6;
    border-radius: 7px;
    overflow: hidden;
    margin-bottom: 8px;
    border: 1px solid #e3e6ec;
}
.pfe-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* ── Preview panel ── */
.pfe-preview__label {
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: #94a3b8;
    margin: 0 0 10px;
    padding: 0 2px;
}

.pfe-preview-card {
    background: #fff;
    border: 1px solid #e3e6ec;
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 10px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.pfe-preview-card__tag {
    display: inline-flex;
    align-items: center;
    height: 20px;
    padding: 0 8px;
    border-radius: 999px;
    background: #e8f5ee;
    color: #065f46;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    width: fit-content;
}
.pfe-preview-card__quote {
    font-size: 12px;
    color: #475569;
    line-height: 1.5;
    font-style: italic;
    margin: 0;
}
.pfe-preview-card__name {
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
}
.pfe-preview-card__loc {
    font-size: 11px;
    color: #94a3b8;
}

.pfe-preview-opp {
    background: #fff;
    border: 1px solid #e3e6ec;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 10px;
}
.pfe-preview-opp__img {
    width: 100%;
    aspect-ratio: 16/9;
    background: #f1f3f6;
    overflow: hidden;
}
.pfe-preview-opp__img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.pfe-preview-opp__body { padding: 12px 14px; display: flex; flex-direction: column; gap: 4px; }
.pfe-preview-opp__body strong { font-size: 12.5px; font-weight: 700; color: #0f172a; }
.pfe-preview-opp__body p { font-size: 11.5px; color: #64748b; line-height: 1.45; margin: 0; }
.pfe-preview-opp__foot { margin-top: 8px; }
.pfe-preview-opp__cta {
    display: inline-flex;
    align-items: center;
    height: 24px;
    padding: 0 10px;
    border-radius: 999px;
    background: #009543;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
}

/* ── Responsive ── */
@media (max-width: 1024px) {
    .pfe-panel__body--split { flex-direction: column; }
    .pfe-panel__preview { position: static; flex: none; width: 100%; }
    .pfe-grid--3 { grid-template-columns: repeat(2, minmax(0,1fr)); }
}
@media (max-width: 720px) {
    .pfe-topbar { padding: 12px 16px; flex-wrap: wrap; }
    .pfe-tabbar { padding: 0 16px; }
    .pfe-panel__body { padding: 0 16px; margin: 16px auto; }
    .pfe-grid--2, .pfe-grid--3 { grid-template-columns: 1fr; }
    .pfe-field--span2 { grid-column: span 1; }
}
</style>

<script>
(function () {
    var root      = document.querySelector('.pfe-root');
    var tabBtns   = root.querySelectorAll('.pfe-tab');
    var panels    = root.querySelectorAll('.pfe-panel');
    var STORAGE_KEY = 'pfe_active_tab_{{ $homeContentWorkspace }}';

    function activate(id) {
        tabBtns.forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-tab') === id);
            btn.setAttribute('aria-selected', btn.getAttribute('data-tab') === id ? 'true' : 'false');
        });
        panels.forEach(function (panel) {
            panel.classList.toggle('is-active', panel.getAttribute('data-panel') === id);
        });
        try { sessionStorage.setItem(STORAGE_KEY, id); } catch (e) {}
    }

    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activate(btn.getAttribute('data-tab'));
        });
    });

    // Restore last tab or activate first
    var firstTab   = tabBtns[0] ? tabBtns[0].getAttribute('data-tab') : null;
    var savedTab   = null;
    try { savedTab = sessionStorage.getItem(STORAGE_KEY); } catch (e) {}
    var hasError   = {{ $errors->any() ? 'true' : 'false' }};
    activate(hasError ? firstTab : (savedTab || firstTab));

    // Live preview sync — text inputs
    root.querySelectorAll('[data-preview-target]').forEach(function (input) {
        var node = root.querySelector('[data-preview-node="' + input.getAttribute('data-preview-target') + '"]');
        if (!node) { return; }
        input.addEventListener('input', function () { node.textContent = input.value; });
    });

    // Live preview sync — file inputs
    root.querySelectorAll('[data-preview-image]').forEach(function (input) {
        var node = root.querySelector('[data-preview-node="' + input.getAttribute('data-preview-image') + '"]');
        if (!node) { return; }
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) { return; }
            var reader = new FileReader();
            reader.onload = function (e) { node.src = e.target.result; };
            reader.readAsDataURL(file);
        });
    });

    // Live preview sync — media library select
    root.querySelectorAll('.js-unified-media-select').forEach(function (select) {
        var node = root.querySelector('[data-preview-node="' + select.getAttribute('data-preview-target') + '"]');
        if (!node) { return; }
        select.addEventListener('change', function () {
            var opt = select.options[select.selectedIndex];
            var url = opt ? opt.getAttribute('data-preview') : '';
            if (url) { node.src = url; }
        });
    });

    // Save shortcut Ctrl/Cmd + S
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            var form = document.getElementById('pfe-form');
            if (form) { form.requestSubmit(); }
        }
    });
})();
</script>
@endsection
