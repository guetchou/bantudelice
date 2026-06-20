@php
    $siteContext = $siteContext ?? app(\App\Services\SiteContextService::class)->bootstrap(request());
    $currentSite = data_get($siteContext, 'site', []);
    $currentLocale = data_get($siteContext, 'locale', app()->getLocale());
    $sites = data_get($siteContext, 'available_sites', []);
    $supportedLocales = data_get($siteContext, 'supported_locales', []);
    $hasLocaleSwitcher = count($supportedLocales) > 1;
    $hasSiteSwitcher = count($sites) > 1;
    $currentUrl = url()->full();
@endphp

@if($hasLocaleSwitcher || $hasSiteSwitcher)
<div class="bd-site-switcher">
    @if($hasLocaleSwitcher)
        <div class="bd-site-switcher__dropdown">
            <button class="bd-site-switcher__trigger" type="button" aria-expanded="false" aria-label="{{ trans('ui.switcher.switch_locale') }}">
                <span class="bd-site-switcher__icon" aria-hidden="true">
                    <i class="fas fa-globe"></i>
                </span>
                <span class="bd-site-switcher__current">{{ strtoupper($currentLocale) }}</span>
                <i class="fas fa-chevron-down bd-site-switcher__chevron" aria-hidden="true"></i>
            </button>
            <div class="bd-site-switcher__menu">
                @foreach($supportedLocales as $locale => $label)
                    <a
                        href="{{ route('site.locale.switch', ['locale' => $locale, 'redirect' => $currentUrl]) }}"
                        class="bd-site-switcher__option {{ $currentLocale === $locale ? 'is-active' : '' }}"
                        @if($currentLocale === $locale) aria-current="true" @endif
                        title="{{ $label }}"
                    >
                        <span>{{ strtoupper($locale) }}</span>
                        <small>{{ $label }}</small>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
    @if($hasSiteSwitcher)
        <div class="bd-site-switcher__sites">
            @foreach($sites as $siteKey => $site)
                <a href="{{ route('site.switch', ['siteKey' => $siteKey, 'redirect' => $currentUrl]) }}" class="bd-site-switcher__pill {{ data_get($currentSite, 'key') === $siteKey ? 'is-active' : '' }}">
                    {{ data_get($site, 'name', $siteKey) }}
                </a>
            @endforeach
        </div>
    @endif
</div>
@endif
