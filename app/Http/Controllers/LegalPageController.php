<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersFrontendBrand;
use App\Services\CmsStaticPageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LegalPageController extends Controller
{
    use RemembersFrontendBrand;

    public function __construct(private readonly CmsStaticPageService $cms) {}

    public function about(Request $request): View
    {
        $this->rememberFrontendBrand($request);
        return $this->cmsOrFallback('about-us', 'frontend.about');
    }

    public function terms(Request $request): View
    {
        $this->rememberFrontendBrand($request);
        return $this->cmsOrFallback('terms-and-conditions', 'frontend.terms');
    }

    public function refundPolicy(): View
    {
        return $this->cmsOrFallback('return-policy', 'frontend.policy');
    }

    public function privacyPolicy(Request $request): View
    {
        $this->rememberFrontendBrand($request);
        return $this->cmsOrFallback('privacy-policy', 'frontend.privacy_policy');
    }

    public function legalNotices(Request $request): View
    {
        $this->rememberFrontendBrand($request);
        return $this->cmsOrFallback('mentions-legales', 'frontend.legal_notices');
    }

    public function cookies(Request $request): View
    {
        $this->rememberFrontendBrand($request);
        return $this->cmsOrFallback('politique-cookies', 'frontend.cookies');
    }

    public function faq(): View
    {
        return $this->cmsOrFallback('faq', 'frontend.faq');
    }

    public function help(Request $request): View
    {
        $this->rememberFrontendBrand($request);
        return $this->cmsOrFallback('help', 'frontend.help');
    }

    public function offers(): View
    {
        return $this->cmsOrFallback('offers', 'frontend.offers');
    }

    private function cmsOrFallback(string $slug, string $fallbackView): View
    {
        $page = $this->cms->getPage($slug);

        if ($page) {
            return view('frontend.cms_page', [
                'page'         => $page,
                'pageBody'     => $this->cms->body($page),
                'pageImage'    => $this->cms->featuredImage($page),
                'pageCtaLabel' => $this->cms->primaryCtaLabel($page),
                'pageCtaUrl'   => $this->cms->primaryCtaUrl($page),
            ]);
        }

        return view($fallbackView);
    }
}
