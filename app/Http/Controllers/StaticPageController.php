<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersFrontendBrand;
use Illuminate\Http\Request;

class StaticPageController extends Controller
{
    use RemembersFrontendBrand;

    public function siteMap(): \Illuminate\Contracts\View\View
    {
        return view('frontend.sitemap');
    }

    public function dataDeletion(Request $request): \Illuminate\Contracts\View\View
    {
        $this->rememberFrontendBrand($request);
        return view('frontend.data_deletion');
    }

    public function contact(Request $request): \Illuminate\Contracts\View\View
    {
        $this->rememberFrontendBrand($request);
        return view('frontend.contact');
    }
}
