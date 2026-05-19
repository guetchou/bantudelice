@extends('layouts.admin-modern')
@section('title', 'Contenu accueil')
@section('page_title', 'Contenu homepage')
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
        1 => [
            'tag' => 'Livraison repas',
            'quote' => 'La commande arrive chaude, proprement emballee et dans les delais annonces.',
            'name' => 'Prisca M.',
            'loc' => 'Centre-ville, Brazzaville',
        ],
        2 => [
            'tag' => 'Service colis',
            'quote' => 'Le suivi est clair et la prise en charge rassurante pour les envois du quotidien.',
            'name' => 'Cedric N.',
            'loc' => 'Littoral congolais',
        ],
        3 => [
            'tag' => 'Transport',
            'quote' => 'Tarif affiche avant confirmation et reservation simple depuis le telephone.',
            'name' => 'Aimee K.',
            'loc' => 'Bacongo, Brazzaville',
        ],
    ];
    $opportunityDefaults = [
        1 => [
            'title' => 'Devenir coursier',
            'body' => "Rejoignez le reseau de la plateforme pour livrer repas et colis avec un parcours d'inscription simple et rapide.",
            'cta' => 'Inscription',
            'url' => route('driver'),
            'image' => 'images/home/service-driver.jpg',
        ],
        2 => [
            'title' => 'Devenir partenaire',
            'body' => 'Restaurants, commerces et enseignes peuvent developper leur visibilite et leurs ventes sur la plateforme.',
            'cta' => 'Inscription',
            'url' => route('partner'),
            'image' => 'images/home/service-restaurant.jpg',
        ],
        3 => [
            'title' => 'Emploi',
            'body' => "Vous souhaitez rejoindre l'equipe plateforme ou proposer votre profil pour un poste operationnel ou support.",
            'cta' => 'Nous contacter',
            'url' => route('contact.us'),
            'image' => 'images/home/service-transport.jpg',
        ],
    ];
    $testimonialPreview = collect([1, 2, 3])->mapWithKeys(function ($index) use ($content, $testimonialDefaults) {
        return [$index => [
            'tag' => old('home_testimonial_' . $index . '_tag', $content['testimonial_' . $index . '_tag'] ?? $testimonialDefaults[$index]['tag']),
            'quote' => old('home_testimonial_' . $index . '_quote', $content['testimonial_' . $index . '_quote'] ?? $testimonialDefaults[$index]['quote']),
            'name' => old('home_testimonial_' . $index . '_name', $content['testimonial_' . $index . '_name'] ?? $testimonialDefaults[$index]['name']),
            'loc' => old('home_testimonial_' . $index . '_loc', $content['testimonial_' . $index . '_loc'] ?? $testimonialDefaults[$index]['loc']),
        ]];
    })->all();
    $opportunityPreview = collect([1, 2, 3])->mapWithKeys(function ($index) use ($content, $opportunityDefaults) {
        return [$index => [
            'title' => old('home_opportunity_' . $index . '_title', $content['opportunity_' . $index . '_title'] ?? $opportunityDefaults[$index]['title']),
            'body' => old('home_opportunity_' . $index . '_body', $content['opportunity_' . $index . '_body'] ?? $opportunityDefaults[$index]['body']),
            'cta' => old('home_opportunity_' . $index . '_cta', $content['opportunity_' . $index . '_cta'] ?? $opportunityDefaults[$index]['cta']),
            'url' => old('home_opportunity_' . $index . '_url', $content['opportunity_' . $index . '_url'] ?? $opportunityDefaults[$index]['url']),
            'image' => asset($content['opportunity_' . $index . '_image'] ?? $opportunityDefaults[$index]['image']),
        ]];
    })->all();
    $isMediaFocus = request('focus') === 'media';
    $editorSectionCount = $showFoodSections ? 5 : 4;
    $mediaSlotCount = count($heroMediaFields) + ($showFoodSections ? 3 : 0) + 3;
    $filledMediaCount = collect([
        'hero_main_image',
        'hero_colis_image',
        'hero_transport_image',
        'service_food_image',
        'service_colis_image',
        'service_transport_image',
        'opportunity_1_image',
        'opportunity_2_image',
        'opportunity_3_image',
    ])->filter(function ($key) use ($content, $showFoodSections) {
        if (!$showFoodSections && in_array($key, ['service_food_image', 'service_colis_image', 'service_transport_image'], true)) {
            return false;
        }

        if (!$showFoodSections && $key === 'hero_main_image') {
            return false;
        }

        return filled((string) ($content[$key] ?? ''));
    })->count();
    $editorJumpLinks = collect([
        ['id' => 'home-content-section-hero', 'label' => 'Hero'],
        $showFoodSections ? ['id' => 'home-content-section-editorial', 'label' => 'Editorial'] : null,
        ['id' => 'home-content-section-testimonials', 'label' => 'Avis'],
        ['id' => 'home-content-section-opportunities', 'label' => 'Opportunites'],
        ['id' => 'home-content-section-support', 'label' => 'Support'],
    ])->filter()->values();
@endphp
<div class="content-header">
    <div class="container-fluid">
        @include('admin.partials.control_hub_nav')
        <div class="bd-admin-editor-shell">
            <section class="bd-admin-editor-hero">
                <div class="bd-admin-editor-hero__content">
                    <p class="bd-admin-editor-hero__eyebrow">{{ $cmsWorkspace['eyebrow'] }}</p>
                    <h1>Contenu de l’accueil {{ $cmsWorkspace['label'] }}</h1>
                    <p>{{ $cmsWorkspace['description'] }}</p>
                    <div class="bd-admin-editor-hero__metrics">
                        <article>
                            <span>Sections</span>
                            <strong>{{ $editorSectionCount }}</strong>
                        </article>
                        <article>
                            <span>Medias actifs</span>
                            <strong>{{ $filledMediaCount }}/{{ $mediaSlotCount }}</strong>
                        </article>
                        <article>
                            <span>Point d entree</span>
                            <strong>{{ $cmsWorkspace['label'] }}</strong>
                        </article>
                    </div>
                </div>
                <div class="bd-admin-editor-hero__badges">
                    <span>{{ $cmsWorkspace['label'] }}</span>
                    <span>Home</span>
                    <a href="{{ $homePreviewUrl }}" target="_blank" rel="noopener" class="bd-admin-editor-hero__preview-link">Ouvrir l’aperçu</a>
                </div>
            </section>
            <section class="bd-admin-editor-summary">
                <div class="bd-admin-editor-summary__head">
                    <div>
                        <p class="bd-admin-editor-summary__eyebrow">Flux d edition</p>
                        <h2>Parcours de mise a jour</h2>
                    </div>
                    <div class="bd-admin-editor-summary__jump">
                        @foreach($editorJumpLinks as $link)
                            <a href="#{{ $link['id'] }}">{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="bd-admin-editor-summary__grid">
                    <article>
                        <span>1. Texte</span>
                        <strong>Mettre a jour les messages clefs</strong>
                        <p>Hero, cartes et CTA sans quitter l editeur.</p>
                    </article>
                    <article>
                        <span>2. Medias</span>
                        <strong>Controler les visuels relies</strong>
                        <p>Upload direct ou choix depuis la mediathèque unifiee.</p>
                    </article>
                    <article>
                        <span>3. Verification</span>
                        <strong>Comparer avec l apercu right rail</strong>
                        <p>Valider le ton, les CTA et l equilibre visuel avant sauvegarde.</p>
                    </article>
                </div>
            </section>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if(!empty($mediaBacklog['slots']))
            <div class="bd-admin-editor-shell mb-4">
                <section class="bd-admin-media-audit {{ $isMediaFocus ? 'is-focused' : '' }}">
                    <div class="bd-admin-media-audit__head">
                        <div>
                            <p class="bd-admin-media-audit__eyebrow">Media backlog</p>
                            <h2>{{ $mediaBacklog['title'] }}</h2>
                            <p>{{ $mediaBacklog['description'] }}</p>
                        </div>
                        <div class="bd-admin-media-audit__stats">
                            <div>
                                <strong>{{ $mediaBacklog['missing_count'] }}</strong>
                                <span>A renseigner</span>
                            </div>
                            <div>
                                <strong>{{ $mediaBacklog['ready_count'] }}</strong>
                                <span>Prets</span>
                            </div>
                        </div>
                    </div>
                    <div class="bd-admin-media-audit__grid">
                        @foreach($mediaBacklog['slots'] as $slot)
                            <a href="#{{ $slot['section_id'] }}" class="bd-admin-media-audit__item {{ $slot['is_ready'] ? 'is-ready' : 'is-missing' }}">
                                <span>{{ $slot['label'] }}</span>
                                <strong>{{ $slot['is_ready'] ? 'Pret' : 'A renseigner' }}</strong>
                                <small>{{ $slot['usage'] }}</small>
                            </a>
                        @endforeach
                    </div>
                </section>
            </div>
        @endif
        <div class="row">
            <div class="col-xl-8 col-lg-7">
                <div class="card bd-admin-editor-card">
                    <div class="card-header border-0">
                        <div class="bd-admin-editor-card__header">
                            <div>
                                <h3>Edition de l’accueil</h3>
                                <p>Chaque bloc met a jour les sections d’accueil de {{ $cmsWorkspace['label'] }} sans repasser par le code.</p>
                            </div>
                        </div>
                    </div>
                    <form method="post" action="{{ route('admin.home-content.update', ['workspace' => $homeContentWorkspace]) }}" enctype="multipart/form-data">
                        @csrf
                        @method('put')
                        <div class="card-body">
                            <div class="bd-admin-form-group" id="home-content-section-hero">
                                <h4>Hero</h4>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Badge</label>
                                        <input type="text" name="home_hero_badge" class="form-control" value="{{ old('home_hero_badge', $content['hero_badge']) }}">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Description courte</label>
                                        <textarea name="home_hero_description" rows="3" class="form-control">{{ old('home_hero_description', $content['hero_description']) }}</textarea>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Titre ligne 1</label>
                                        <input type="text" name="home_hero_title_line_1" class="form-control" value="{{ old('home_hero_title_line_1', $content['hero_title_line_1']) }}">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Titre ligne 2</label>
                                        <input type="text" name="home_hero_title_line_2" class="form-control" value="{{ old('home_hero_title_line_2', $content['hero_title_line_2']) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="bd-admin-form-group" id="home-content-media-hero">
                                <h4>Visuels hero</h4>
                                <div class="row">
                                    @foreach($heroMediaFields as $field)
                                        <div class="{{ count($heroMediaFields) === 1 ? 'col-md-6' : 'col-md-4' }} form-group">
                                            <label>{{ $field['label'] }}</label>
                                            <input type="file" name="{{ $field['input'] }}" class="form-control" data-preview-image="{{ $field['preview_target'] }}">
                                            @include('partials.unified_media_select', [
                                                'name' => $field['select'],
                                                'label' => 'Ou choisir dans la médiathèque',
                                                'options' => $mediaLibraryOptions ?? [],
                                                'selected' => $field['value'],
                                                'previewTarget' => $field['preview_target'],
                                            ])
                                            @if(!empty($field['value']))
                                                <img src="{{ asset($field['value']) }}" alt="" class="bd-admin-media-thumb" data-preview-node="{{ $field['preview_target'] }}">
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @if($showFoodSections)
                                <div class="bd-admin-form-group" id="home-content-section-editorial">
                                    <h4>Sections editoriales</h4>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="bd-admin-subcard">
                                                <h5>Restaurants populaires</h5>
                                                <div class="form-group">
                                                    <label>Tag</label>
                                                    <input type="text" name="home_restaurants_tag" class="form-control" value="{{ old('home_restaurants_tag', $content['restaurants_tag'] ?? 'Partenaires selectionnes') }}">
                                                </div>
                                                <div class="form-group">
                                                    <label>Titre</label>
                                                    <input type="text" name="home_restaurants_title" class="form-control" value="{{ old('home_restaurants_title', $content['restaurants_title'] ?? 'Restaurants populaires') }}">
                                                </div>
                                                <div class="form-group mb-0">
                                                    <label>Sous-titre</label>
                                                    <input type="text" name="home_restaurants_subtitle" class="form-control" value="{{ old('home_restaurants_subtitle', $content['restaurants_subtitle'] ?? '') }}">
                                                </div>
                                            </div>

                                            <div class="bd-admin-subcard mt-4">
                                                <h5>Plats populaires</h5>
                                                <div class="form-group">
                                                    <label>Tag</label>
                                                    <input type="text" name="home_popular_products_tag" class="form-control" value="{{ old('home_popular_products_tag', $content['popular_products_tag'] ?? 'Selection du moment') }}">
                                                </div>
                                                <div class="form-group">
                                                    <label>Titre</label>
                                                    <input type="text" name="home_popular_products_title" class="form-control" value="{{ old('home_popular_products_title', $content['popular_products_title'] ?? 'Plats a decouvrir') }}">
                                                </div>
                                                <div class="form-group mb-0">
                                                    <label>Sous-titre</label>
                                                    <input type="text" name="home_popular_products_subtitle" class="form-control" value="{{ old('home_popular_products_subtitle', $content['popular_products_subtitle'] ?? '') }}">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="bd-admin-subcard">
                                                <h5>Trois services</h5>
                                                <div class="form-group">
                                                    <label>Titre</label>
                                                    <input type="text" name="home_services_title" class="form-control" value="{{ old('home_services_title', $content['services_title']) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label>Sous-titre</label>
                                                    <textarea name="home_services_subtitle" rows="3" class="form-control">{{ old('home_services_subtitle', $content['services_subtitle']) }}</textarea>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-4 form-group">
                                                        <label>Repas</label>
                                                        <input type="file" name="home_service_food_image" class="form-control" data-preview-image="service-food-image">
                                                        @include('partials.unified_media_select', [
                                                            'name' => 'home_service_food_image_media_path',
                                                            'label' => 'Ou choisir',
                                                            'options' => $mediaLibraryOptions ?? [],
                                                            'selected' => $content['service_food_image'] ?? '',
                                                            'previewTarget' => 'service-food-image',
                                                        ])
                                                        @if($content['service_food_image'])
                                                            <img src="{{ asset($content['service_food_image']) }}" alt="" class="bd-admin-media-thumb bd-admin-media-thumb--small" data-preview-node="service-food-image">
                                                        @endif
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>Colis</label>
                                                        <input type="file" name="home_service_colis_image" class="form-control" data-preview-image="service-colis-image">
                                                        @include('partials.unified_media_select', [
                                                            'name' => 'home_service_colis_image_media_path',
                                                            'label' => 'Ou choisir',
                                                            'options' => $mediaLibraryOptions ?? [],
                                                            'selected' => $content['service_colis_image'] ?? '',
                                                            'previewTarget' => 'service-colis-image',
                                                        ])
                                                        @if($content['service_colis_image'])
                                                            <img src="{{ asset($content['service_colis_image']) }}" alt="" class="bd-admin-media-thumb bd-admin-media-thumb--small" data-preview-node="service-colis-image">
                                                        @endif
                                                    </div>
                                                    <div class="col-md-4 form-group mb-0">
                                                        <label>Transport</label>
                                                        <input type="file" name="home_service_transport_image" class="form-control" data-preview-image="service-transport-image">
                                                        @include('partials.unified_media_select', [
                                                            'name' => 'home_service_transport_image_media_path',
                                                            'label' => 'Ou choisir',
                                                            'options' => $mediaLibraryOptions ?? [],
                                                            'selected' => $content['service_transport_image'] ?? '',
                                                            'previewTarget' => 'service-transport-image',
                                                        ])
                                                        @if($content['service_transport_image'])
                                                            <img src="{{ asset($content['service_transport_image']) }}" alt="" class="bd-admin-media-thumb bd-admin-media-thumb--small" data-preview-node="service-transport-image">
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="bd-admin-form-group" id="home-content-section-testimonials">
                                <h4>Avis clients</h4>
                                <div class="bd-admin-subcard">
                                    <div class="row">
                                        <div class="col-md-4 form-group">
                                            <label>Tag</label>
                                            <input type="text" name="home_testimonials_tag" class="form-control" value="{{ old('home_testimonials_tag', $content['testimonials_tag'] ?? 'Avis clients') }}">
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label>Titre</label>
                                            <input type="text" name="home_testimonials_title" class="form-control" value="{{ old('home_testimonials_title', $content['testimonials_title'] ?? 'Une confiance qui se construit') }}">
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label>Sous-titre</label>
                                            <input type="text" name="home_testimonials_subtitle" class="form-control" value="{{ old('home_testimonials_subtitle', $content['testimonials_subtitle'] ?? '') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-4">
                                    @foreach([1, 2, 3] as $index)
                                        <div class="col-md-4">
                                            <div class="bd-admin-subcard h-100">
                                                <h5>Carte {{ $index }}</h5>
                                                <div class="form-group">
                                                    <label>Tag</label>
                                                <input type="text" name="home_testimonial_{{ $index }}_tag" class="form-control" data-preview-target="testimonial-{{ $index }}-tag" value="{{ old('home_testimonial_' . $index . '_tag', $content['testimonial_' . $index . '_tag'] ?? $testimonialDefaults[$index]['tag']) }}">
                                            </div>
                                            <div class="form-group">
                                                <label>Avis</label>
                                                <textarea name="home_testimonial_{{ $index }}_quote" rows="4" class="form-control" data-preview-target="testimonial-{{ $index }}-quote">{{ old('home_testimonial_' . $index . '_quote', $content['testimonial_' . $index . '_quote'] ?? $testimonialDefaults[$index]['quote']) }}</textarea>
                                            </div>
                                            <div class="form-group">
                                                <label>Nom</label>
                                                <input type="text" name="home_testimonial_{{ $index }}_name" class="form-control" data-preview-target="testimonial-{{ $index }}-name" value="{{ old('home_testimonial_' . $index . '_name', $content['testimonial_' . $index . '_name'] ?? $testimonialDefaults[$index]['name']) }}">
                                            </div>
                                            <div class="form-group mb-0">
                                                <label>Localisation</label>
                                                <input type="text" name="home_testimonial_{{ $index }}_loc" class="form-control" data-preview-target="testimonial-{{ $index }}-loc" value="{{ old('home_testimonial_' . $index . '_loc', $content['testimonial_' . $index . '_loc'] ?? $testimonialDefaults[$index]['loc']) }}">
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="bd-admin-form-group" id="home-content-section-opportunities">
                                <h4>Opportunites</h4>
                                <div class="bd-admin-subcard">
                                    <div class="row">
                                        <div class="col-md-4 form-group">
                                            <label>Tag</label>
                                            <input type="text" name="home_opportunities_tag" class="form-control" value="{{ old('home_opportunities_tag', $content['opportunities_tag'] ?? 'Opportunites') }}">
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label>Titre</label>
                                            <input type="text" name="home_opportunities_title" class="form-control" value="{{ old('home_opportunities_title', $content['opportunities_title'] ?? 'Grandissez avec la plateforme') }}">
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label>Sous-titre</label>
                                            <textarea name="home_opportunities_subtitle" rows="2" class="form-control">{{ old('home_opportunities_subtitle', $content['opportunities_subtitle'] ?? 'Que vous soyez coursier, enseigne, commerce ou candidat, la plateforme ouvre des relais de croissance concrets pour accompagner le quotidien au Congo.') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-4">
                                    @foreach([1, 2, 3] as $index)
                                        <div class="col-md-4">
                                            <div class="bd-admin-subcard h-100">
                                                <h5>Carte {{ $index }}</h5>
                                                <div class="form-group">
                                                    <label>Titre</label>
                                                    <input type="text" name="home_opportunity_{{ $index }}_title" class="form-control" data-preview-target="opportunity-{{ $index }}-title" value="{{ old('home_opportunity_' . $index . '_title', $content['opportunity_' . $index . '_title'] ?? $opportunityDefaults[$index]['title']) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label>Texte</label>
                                                    <textarea name="home_opportunity_{{ $index }}_body" rows="4" class="form-control" data-preview-target="opportunity-{{ $index }}-body">{{ old('home_opportunity_' . $index . '_body', $content['opportunity_' . $index . '_body'] ?? $opportunityDefaults[$index]['body']) }}</textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label>Label bouton</label>
                                                    <input type="text" name="home_opportunity_{{ $index }}_cta" class="form-control" data-preview-target="opportunity-{{ $index }}-cta" value="{{ old('home_opportunity_' . $index . '_cta', $content['opportunity_' . $index . '_cta'] ?? $opportunityDefaults[$index]['cta']) }}">
                                                </div>
                                                <div class="form-group">
                                                    <label>URL bouton</label>
                                                    <input type="text" name="home_opportunity_{{ $index }}_url" class="form-control" data-preview-target="opportunity-{{ $index }}-url" value="{{ old('home_opportunity_' . $index . '_url', $content['opportunity_' . $index . '_url'] ?? $opportunityDefaults[$index]['url']) }}">
                                                </div>
                                                <div class="form-group mb-0">
                                                    <label>Image</label>
                                                    <input type="file" name="home_opportunity_{{ $index }}_image" class="form-control" data-preview-image="opportunity-{{ $index }}-image">
                                                    @include('partials.unified_media_select', [
                                                        'name' => 'home_opportunity_' . $index . '_image_media_path',
                                                        'label' => 'Ou choisir dans la médiathèque',
                                                        'options' => $mediaLibraryOptions ?? [],
                                                        'selected' => $content['opportunity_' . $index . '_image'] ?? '',
                                                        'previewTarget' => 'opportunity-' . $index . '-image',
                                                    ])
                                                    @if(!empty($content['opportunity_' . $index . '_image']) || !empty($opportunityDefaults[$index]['image']))
                                                        <img src="{{ asset($content['opportunity_' . $index . '_image'] ?? $opportunityDefaults[$index]['image']) }}" alt="" class="bd-admin-media-thumb bd-admin-media-thumb--small">
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="bd-admin-form-group" id="home-content-section-support">
                                <h4>Bloc d’accompagnement</h4>
                                <div class="row">
                                    <div class="col-md-4 form-group">
                                        <label>Titre</label>
                                        <input type="text" name="home_support_title" class="form-control" value="{{ old('home_support_title', $content['support_title'] ?? '') }}">
                                    </div>
                                    <div class="col-md-5 form-group">
                                        <label>Description</label>
                                        <textarea name="home_support_description" rows="3" class="form-control">{{ old('home_support_description', $content['support_description'] ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Bouton</label>
                                        <input type="text" name="home_support_cta_text" class="form-control" value="{{ old('home_support_cta_text', $content['support_cta_text'] ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer border-0 d-flex justify-content-between align-items-center">
                            <span class="text-muted">Les changements sont visibles sur la home publique apres mise a jour.</span>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-xl-4 col-lg-5">
                <div class="bd-admin-editor-side">
                <div class="card bd-admin-editor-card">
                    <div class="card-header border-0">
                        <h3>Acces CMS</h3>
                    </div>
                    <div class="card-body">
                        @if(!empty($cmsSections))
                            <div class="alert alert-info">
                                Ce formulaire pilote maintenant les sections CMS de l’accueil {{ $cmsWorkspace['label'] }}.
                            </div>
                            <div class="bd-admin-link-stack">
                                @foreach($cmsSections as $section)
                                    <a href="{{ route('admin.cms.contents.edit', ['content' => $section['id'], 'workspace' => $homeContentWorkspace ?: 'bantudelice']) }}">{{ $section['title'] ?: $section['id'] }}</a>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted mb-0">Les sections CMS seront listees ici apres initialisation.</p>
                        @endif
                    </div>
                </div>

                <div class="card bd-admin-editor-card mt-4">
                    <div class="card-header border-0">
                        <h3>Apercu visuel</h3>
                    </div>
                    <div class="card-body">
                        <div class="bd-admin-preview-block">
                            <div class="bd-admin-preview-block__head">
                                <span>Avis clients</span>
                                <small>Rendu admin</small>
                            </div>
                            <div class="bd-admin-preview-testimonials">
                                @foreach([1, 2, 3] as $index)
                                    <article class="bd-admin-preview-testimonial {{ $index === 2 ? 'is-featured' : '' }}">
                                        <span class="bd-admin-preview-testimonial__tag" data-preview-node="testimonial-{{ $index }}-tag">{{ $testimonialPreview[$index]['tag'] }}</span>
                                        <div class="bd-admin-preview-testimonial__quote" data-preview-node="testimonial-{{ $index }}-quote">« {{ $testimonialPreview[$index]['quote'] }} »</div>
                                        <div class="bd-admin-preview-testimonial__name" data-preview-node="testimonial-{{ $index }}-name">{{ $testimonialPreview[$index]['name'] }}</div>
                                        <div class="bd-admin-preview-testimonial__loc" data-preview-node="testimonial-{{ $index }}-loc">{{ $testimonialPreview[$index]['loc'] }}</div>
                                    </article>
                                @endforeach
                            </div>
                        </div>

                        <div class="bd-admin-preview-block mt-4">
                            <div class="bd-admin-preview-block__head">
                                <span>Opportunites</span>
                                <small>Images et CTA</small>
                            </div>
                            <div class="bd-admin-preview-opportunities">
                                @foreach([1, 2, 3] as $index)
                                    <article class="bd-admin-preview-opportunity">
                                        <div class="bd-admin-preview-opportunity__media">
                                            <img src="{{ $opportunityPreview[$index]['image'] }}" alt="" data-preview-node="opportunity-{{ $index }}-image">
                                        </div>
                                        <div class="bd-admin-preview-opportunity__body">
                                            <h5 data-preview-node="opportunity-{{ $index }}-title">{{ $opportunityPreview[$index]['title'] }}</h5>
                                            <p data-preview-node="opportunity-{{ $index }}-body">{{ $opportunityPreview[$index]['body'] }}</p>
                                            <div class="bd-admin-preview-opportunity__footer">
                                                <span class="bd-admin-preview-opportunity__cta" data-preview-node="opportunity-{{ $index }}-cta">{{ $opportunityPreview[$index]['cta'] }}</span>
                                                <small data-preview-node="opportunity-{{ $index }}-url">{{ $opportunityPreview[$index]['url'] }}</small>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .bd-admin-editor-shell { display:grid; gap:20px; }
    .bd-admin-editor-hero { display:flex; justify-content:space-between; align-items:flex-start; gap:24px; padding:24px 26px; border-radius:26px; border:1px solid #dbe5ef; background:linear-gradient(180deg,#ffffff 0%,#f8fbfd 100%); color:#0f172a; box-shadow:0 18px 40px rgba(15,23,42,.06); }
    .bd-admin-editor-hero__content { display:grid; gap:14px; min-width:0; }
    .bd-admin-editor-hero__eyebrow { margin:0; font-size:.72rem; letter-spacing:.16em; text-transform:uppercase; font-weight:800; color:#0f766e; }
    .bd-admin-editor-hero h1 { margin:0; color:#020617 !important; font-size:clamp(1.8rem,3vw,2.6rem); font-weight:900; line-height:1.05; letter-spacing:-.04em; }
    .bd-admin-editor-hero p { margin:0; max-width:760px; color:#475569; line-height:1.7; }
    .bd-admin-editor-hero__metrics { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; max-width:760px; }
    .bd-admin-editor-hero__metrics article { padding:14px 16px; border-radius:18px; border:1px solid #dbe5ef; background:#f8fafc; }
    .bd-admin-editor-hero__metrics span { display:block; color:#64748b; font-size:.72rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
    .bd-admin-editor-hero__metrics strong { display:block; margin-top:6px; color:#0f172a; font-size:1.02rem; font-weight:900; }
    .bd-admin-editor-hero__badges { display:grid; gap:10px; justify-items:flex-end; }
    .bd-admin-editor-hero__badges span { display:inline-flex; min-height:38px; align-items:center; justify-content:center; min-width:120px; padding:0 14px; border-radius:999px; background:#f8fafc; border:1px solid #dbe5ef; font-weight:800; color:#0f172a; }
    .bd-admin-editor-hero__preview-link { display:inline-flex; min-height:40px; align-items:center; justify-content:center; min-width:180px; padding:0 16px; border-radius:999px; background:#0f172a; color:#fff; text-decoration:none; font-weight:800; }
    .bd-admin-editor-summary { display:grid; gap:16px; padding:18px 22px; border-radius:22px; border:1px solid #dbe5ef; background:#ffffff; box-shadow:0 14px 30px rgba(15,23,42,.04); }
    .bd-admin-editor-summary__head { display:flex; align-items:flex-start; justify-content:space-between; gap:18px; flex-wrap:wrap; }
    .bd-admin-editor-summary__eyebrow { margin:0 0 6px; color:#64748b; font-size:.72rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; }
    .bd-admin-editor-summary__head h2 { margin:0; color:#0f172a; font-size:1.2rem; font-weight:900; letter-spacing:-.03em; }
    .bd-admin-editor-summary__jump { display:flex; gap:10px; flex-wrap:wrap; }
    .bd-admin-editor-summary__jump a { display:inline-flex; align-items:center; min-height:34px; padding:0 12px; border-radius:999px; background:#f8fafc; border:1px solid #dbe5ef; color:#334155; font-size:.76rem; font-weight:800; text-decoration:none; }
    .bd-admin-editor-summary__grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
    .bd-admin-editor-summary__grid article { padding:14px 16px; border-radius:18px; background:#f8fafc; border:1px solid #e2e8f0; }
    .bd-admin-editor-summary__grid span { display:block; color:#0f766e; font-size:.7rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; }
    .bd-admin-editor-summary__grid strong { display:block; margin-top:8px; color:#0f172a; font-size:.95rem; font-weight:900; }
    .bd-admin-editor-summary__grid p { margin:8px 0 0; color:#64748b; font-size:.82rem; line-height:1.6; }
    .bd-admin-editor-card { border-radius:22px !important; border-color:#dbe5ef !important; background:#ffffff !important; box-shadow:0 16px 36px rgba(15,23,42,.05) !important; }
    .bd-admin-editor-side { position:sticky; top:92px; display:grid; gap:16px; }
    .bd-admin-editor-card__header h3, .bd-admin-editor-card .card-header h3 { margin:0; color:#020617; font-size:1.2rem; font-weight:900; }
    .bd-admin-editor-card__header p { margin:8px 0 0; color:#64748b; line-height:1.7; }
    .bd-admin-form-group + .bd-admin-form-group { margin-top:28px; padding-top:28px; border-top:1px solid #e2e8f0; }
    .bd-admin-form-group h4 { margin:0 0 18px; color:#020617; font-size:1.05rem; font-weight:900; letter-spacing:-.02em; }
    .bd-admin-media-audit { border:1px solid #dbe5ef; border-radius:24px; background:#ffffff; padding:20px 22px; box-shadow:0 16px 34px rgba(15,23,42,.05); }
    .bd-admin-media-audit.is-focused { border-color:#2563eb; box-shadow:0 24px 60px rgba(37,99,235,.14); }
    .bd-admin-media-audit__head { display:flex; justify-content:space-between; align-items:flex-start; gap:18px; flex-wrap:wrap; }
    .bd-admin-media-audit__eyebrow { margin:0 0 8px; color:#64748b; font-size:.72rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; }
    .bd-admin-media-audit h2 { margin:0; color:#020617; font-size:1.35rem; font-weight:900; }
    .bd-admin-media-audit p { margin:.55rem 0 0; color:#475569; max-width:760px; line-height:1.7; }
    .bd-admin-media-audit__stats { display:grid; grid-template-columns:repeat(2,minmax(120px,1fr)); gap:12px; min-width:220px; }
    .bd-admin-media-audit__stats div { border-radius:18px; background:#f8fafc; border:1px solid #e2e8f0; padding:14px 16px; }
    .bd-admin-media-audit__stats strong { display:block; color:#020617; font-size:1.4rem; line-height:1; }
    .bd-admin-media-audit__stats span { display:block; margin-top:6px; color:#475569; font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; }
    .bd-admin-media-audit__grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-top:18px; }
    .bd-admin-media-audit__item { display:grid; gap:6px; text-decoration:none; border-radius:18px; padding:14px 16px; background:#f8fafc; border:1px solid #e2e8f0; box-shadow:none; }
    .bd-admin-media-audit__item span { color:#0f172a; font-size:.86rem; font-weight:800; }
    .bd-admin-media-audit__item strong { font-size:.78rem; letter-spacing:.08em; text-transform:uppercase; }
    .bd-admin-media-audit__item small { color:#64748b; line-height:1.5; }
    .bd-admin-media-audit__item.is-ready strong { color:#0f766e; }
    .bd-admin-media-audit__item.is-missing strong { color:#b91c1c; }
    .bd-admin-subcard { padding:18px; border-radius:18px; background:#f8fafc; border:1px solid #e2e8f0; }
    .bd-admin-subcard h5 { margin:0 0 16px; color:#0f172a; font-size:.96rem; font-weight:900; }
    .bd-admin-media-thumb { display:block; width:100%; max-width:220px; margin-top:12px; border-radius:16px; box-shadow:0 12px 30px rgba(15,23,42,.08); }
    .bd-admin-media-thumb--small { max-width:120px; }
    .bd-admin-link-stack { display:grid; gap:12px; }
    .bd-admin-link-stack a { display:flex; align-items:center; min-height:46px; padding:0 16px; border-radius:14px; background:#f8fafc; border:1px solid #e2e8f0; color:#0f172a; text-decoration:none; font-weight:800; }
    .bd-admin-preview-block + .bd-admin-preview-block { border-top:1px solid #e2e8f0; padding-top:24px; }
    .bd-admin-preview-block__head { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; }
    .bd-admin-preview-block__head span { color:#020617; font-size:1rem; font-weight:900; }
    .bd-admin-preview-block__head small { color:#64748b; font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; }
    .bd-admin-preview-testimonials,
    .bd-admin-preview-opportunities { display:grid; gap:14px; }
    .bd-admin-preview-testimonial { padding:16px; border-radius:18px; background:#f8fafc; border:1px solid #e2e8f0; box-shadow:none; }
    .bd-admin-preview-testimonial.is-featured { border-color:#cbd5e1; background:#eef4f7; }
    .bd-admin-preview-testimonial__tag { display:inline-flex; padding:6px 10px; border-radius:999px; background:#e2f4ec; color:#0f766e; font-size:.7rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
    .bd-admin-preview-testimonial.is-featured .bd-admin-preview-testimonial__tag { background:#dbeafe; color:#1d4ed8; }
    .bd-admin-preview-testimonial__quote { margin-top:12px; color:#0f172a; font-size:.92rem; line-height:1.7; font-weight:700; }
    .bd-admin-preview-testimonial__name { margin-top:14px; color:#0f172a; font-size:.9rem; font-weight:900; }
    .bd-admin-preview-testimonial__loc { margin-top:4px; color:#64748b; font-size:.82rem; }
    .bd-admin-preview-opportunity { overflow:hidden; border-radius:18px; background:#fff; border:1px solid #e2e8f0; box-shadow:none; }
    .bd-admin-preview-opportunity__media { aspect-ratio:16/9; background:#e2e8f0; border-bottom:1px solid #e2e8f0; }
    .bd-admin-preview-opportunity__media img { width:100%; height:100%; object-fit:cover; display:block; }
    .bd-admin-preview-opportunity__body { padding:16px; }
    .bd-admin-preview-opportunity__body h5 { margin:0; color:#020617; font-size:.96rem; font-weight:900; }
    .bd-admin-preview-opportunity__body p { margin:10px 0 0; color:#475569; line-height:1.65; font-size:.86rem; }
    .bd-admin-preview-opportunity__footer { display:flex; justify-content:space-between; align-items:flex-end; gap:12px; margin-top:14px; }
    .bd-admin-preview-opportunity__cta { display:inline-flex; align-items:center; min-height:34px; padding:0 12px; border-radius:999px; background:#0f766e; color:#fff; font-size:.76rem; font-weight:800; }
    .bd-admin-preview-opportunity__footer small { color:#64748b; font-size:.7rem; line-height:1.5; text-align:right; word-break:break-word; }
    @media (max-width: 1199.98px) {
        .bd-admin-editor-side { position:static; }
    }
    @media (max-width: 991.98px) {
        .bd-admin-editor-hero { flex-direction:column; align-items:flex-start; }
        .bd-admin-editor-hero__badges { justify-items:flex-start; }
        .bd-admin-editor-hero__metrics,
        .bd-admin-editor-summary__grid { grid-template-columns:1fr; }
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-preview-target]').forEach(function (input) {
        var key = input.getAttribute('data-preview-target');
        var node = document.querySelector('[data-preview-node="' + key + '"]');
        if (!node) {
            return;
        }

        var syncValue = function () {
            var value = input.value.trim();
            node.textContent = key.indexOf('quote') !== -1 ? '« ' + value + ' »' : value;
        };

        input.addEventListener('input', syncValue);
    });

    document.querySelectorAll('[data-preview-image]').forEach(function (input) {
        var key = input.getAttribute('data-preview-image');
        var node = document.querySelector('[data-preview-node="' + key + '"]');
        if (!node) {
            return;
        }

        input.addEventListener('change', function (event) {
            var file = event.target.files && event.target.files[0];
            if (!file) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function (loadEvent) {
                node.setAttribute('src', loadEvent.target.result);
            };
            reader.readAsDataURL(file);
        });
    });

    document.querySelectorAll('.js-unified-media-select').forEach(function (select) {
        var key = select.getAttribute('data-preview-target');
        var node = key ? document.querySelector('[data-preview-node="' + key + '"]') : null;
        if (!node) {
            return;
        }

        select.addEventListener('change', function () {
            var option = select.options[select.selectedIndex];
            var previewUrl = option ? option.getAttribute('data-preview') : '';
            if (previewUrl) {
                node.setAttribute('src', previewUrl);
            }
        });
    });
});
</script>
@endsection
