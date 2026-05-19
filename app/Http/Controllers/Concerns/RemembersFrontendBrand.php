<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait RemembersFrontendBrand
{
    protected function rememberFrontendBrand(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $brand = strtolower(trim((string) $request->query('brand', '')));
        if (in_array($brand, ['bantudelice', 'mema', 'kende'], true)) {
            $request->session()->put('frontend_brand', $brand);
        }
    }
}
