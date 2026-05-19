<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;

class SiteContextService
{
    public function currentContext(?Request $request = null): array
    {
        $request = $request ?: request();
        $siteKey = $this->resolveSiteKey($request);
        $site = $this->siteConfig($siteKey);
        $locale = $this->resolveLocale($request, $siteKey);

        return [
            'site_key' => $siteKey,
            'site' => $site,
            'locale' => $locale,
            'locale_label' => $this->localeLabel($locale, $site),
            'supported_locales' => $site['supported_locales'] ?? ['fr' => 'Français'],
            'available_sites' => $this->availableSites(),
            'brand_name' => $site['name'] ?? config('app.name'),
            'theme' => $site['theme'] ?? 'modern',
        ];
    }

    public function bootstrap(?Request $request = null): array
    {
        $context = $this->currentContext($request);

        App::setLocale($context['locale']);
        View::share('siteContext', $context);
        View::share('availableSites', $context['available_sites']);
        View::share('availableLocales', $context['supported_locales']);

        if ($request && $request->hasSession()) {
            $request->session()->put('site_key', $context['site_key']);
            $request->session()->put('site_locale', $context['locale']);
        }

        return $context;
    }

    public function switchLocale(string $locale, ?Request $request = null): array
    {
        $request = $request ?: request();
        $siteKey = $this->resolveSiteKey($request);
        $site = $this->siteConfig($siteKey);
        $locale = $this->normalizeLocale($locale, $site);

        if ($request && $request->hasSession()) {
            $request->session()->put('site_locale', $locale);
        }

        App::setLocale($locale);

        return $this->currentContext($request);
    }

    public function switchSite(string $siteKey, ?Request $request = null): array
    {
        $request = $request ?: request();
        $site = $this->siteConfig($siteKey);
        $locale = $this->resolveLocale($request, $siteKey);

        if ($request && $request->hasSession()) {
            $request->session()->put('site_key', $siteKey);
            $request->session()->put('site_locale', $locale);
        }

        App::setLocale($locale);

        return $this->currentContext($request);
    }

    public function availableSites(): array
    {
        $sites = [];

        foreach (config('sites.sites', []) as $key => $site) {
            if (!($site['active'] ?? true)) {
                continue;
            }

            $sites[$key] = array_merge($site, ['key' => $key]);
        }

        return $sites;
    }

    public function currentSiteKey(?Request $request = null): string
    {
        return $this->resolveSiteKey($request ?: request());
    }

    public function currentLocale(?Request $request = null): string
    {
        return $this->resolveLocale($request ?: request(), $this->resolveSiteKey($request ?: request()));
    }

    protected function resolveSiteKey(Request $request): string
    {
        $sites = config('sites.sites', []);
        $defaultSite = config('sites.default_site', 'main');
        $host = strtolower((string) $request->getHost());

        if ($request->hasSession()) {
            $sessionSite = (string) $request->session()->get('site_key', '');
            if ($sessionSite && isset($sites[$sessionSite]) && ($sites[$sessionSite]['active'] ?? true)) {
                return $sessionSite;
            }
        }

        if ($request->query('site') && isset($sites[$request->query('site')])) {
            $querySite = (string) $request->query('site');
            if ($sites[$querySite]['active'] ?? true) {
                return $querySite;
            }
        }

        foreach ($sites as $key => $site) {
            if (!($site['active'] ?? true)) {
                continue;
            }

            foreach (($site['domains'] ?? []) as $domain) {
                if ($domain && strtolower((string) $domain) === $host) {
                    return $key;
                }
            }
        }

        $firstAvailableSite = array_key_first($this->availableSites());

        return isset($sites[$defaultSite]) ? $defaultSite : ($firstAvailableSite ?: 'main');
    }

    protected function resolveLocale(Request $request, string $siteKey): string
    {
        $site = $this->siteConfig($siteKey);
        $supportedLocales = array_keys($site['supported_locales'] ?? ['fr' => 'Français']);
        $defaultLocale = $site['default_locale'] ?? config('sites.fallback_locale', config('app.locale', 'fr'));

        $candidate = null;
        if ($request->query('lang')) {
            $candidate = (string) $request->query('lang');
        } elseif ($request->hasSession()) {
            $candidate = (string) $request->session()->get('site_locale', '');
        }

        return $this->normalizeLocale($candidate ?: $defaultLocale, $site, $supportedLocales);
    }

    protected function normalizeLocale(?string $locale, array $site = [], ?array $supportedLocales = null): string
    {
        $supportedLocales = $supportedLocales ?: array_keys($site['supported_locales'] ?? ['fr' => 'Français']);
        $fallback = config('sites.fallback_locale', config('app.locale', 'fr'));
        $locale = strtolower(trim((string) $locale));

        if ($locale && in_array($locale, $supportedLocales, true)) {
            return $locale;
        }

        $siteDefault = $site['default_locale'] ?? $fallback;
        if (in_array($siteDefault, $supportedLocales, true)) {
            return $siteDefault;
        }

        return in_array($fallback, $supportedLocales, true) ? $fallback : ($supportedLocales[0] ?? 'fr');
    }

    protected function localeLabel(string $locale, array $site): string
    {
        return $site['supported_locales'][$locale] ?? strtoupper($locale);
    }

    protected function siteConfig(string $siteKey): array
    {
        $site = config("sites.sites.{$siteKey}", []);
        if (!$site) {
            $siteKey = config('sites.default_site', 'main');
            $site = config("sites.sites.{$siteKey}", []);
        }

        return array_merge($site, ['key' => $siteKey]);
    }
}
