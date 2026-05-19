<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class AdminArchitectureController extends Controller
{
    public function show(): Response
    {
        return $this->preview();
    }

    public function preview(): Response
    {
        $path = $this->resolveMaquettePath();

        abort_unless($path !== null, 404, 'Maquette admin introuvable.');

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    private function resolveMaquettePath(): ?string
    {
        $candidates = [
            base_path('maquette/bantudelice-admin-ops-maquette.html'),
            dirname(base_path(), 2) . '/maquette/bantudelice-admin-ops-maquette.html',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
