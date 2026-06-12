<?php

namespace App\Http\Controllers;

use App\Services\SiteContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;

class SiteLocaleController extends BaseController
{
    public function switchLocale(Request $request, string $locale): RedirectResponse
    {
        app(SiteContextService::class)->switchLocale($locale, $request);

        return redirect()->to($this->resolveRedirectTarget($request));
    }

    public function switchSite(Request $request, string $siteKey): RedirectResponse
    {
        app(SiteContextService::class)->switchSite($siteKey, $request);

        return redirect()->to($this->resolveRedirectTarget($request));
    }

    protected function resolveRedirectTarget(Request $request): string
    {
        $redirect = (string) $request->query('redirect', '');
        if ($redirect !== '') {
            if (Str::startsWith($redirect, ['/'])) {
                return $redirect;
            }

            $targetHost = parse_url($redirect, PHP_URL_HOST);
            $currentHost = $request->getHost();
            if ($targetHost && $currentHost && strcasecmp($targetHost, $currentHost) === 0) {
                return $redirect;
            }
        }

        return url()->previous() ?: route('home');
    }
}
