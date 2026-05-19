<?php

namespace App\Http\Controllers\admin;

use App\CmsMediaAsset;
use App\Http\Controllers\Controller;
use App\Services\CmsAccessService;
use App\Services\CmsMediaService;
use App\Services\ConfigService;
use App\Services\UnifiedMediaLibraryService;
use Illuminate\Http\Request;

class CmsMediaController extends Controller
{
    public function __construct(
        private CmsAccessService $cmsAccessService,
        private CmsMediaService $cmsMediaService,
        private UnifiedMediaLibraryService $unifiedMediaLibraryService
    ) {
    }

    public function index()
    {
        $this->cmsAccessService->authorize(auth()->user(), 'view');

        $assets = $this->unifiedMediaLibraryService->paginatedAssets(24, (int) request()->integer('page', 1));
        $usageGuide = $this->usageGuide();

        return view('admin.cms.media.index', compact('assets', 'usageGuide'))->with('cmsWorkspace', $this->workspaceMeta());
    }

    public function store(Request $request)
    {
        $this->cmsAccessService->authorize(auth()->user(), 'upload_media');

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:8192',
            'title' => 'nullable|string|max:191',
            'alt_text' => 'nullable|string|max:191',
        ]);

        $this->cmsMediaService->store(
            $request->file('file'),
            optional(auth()->user())->id,
            $request->input('title'),
            $request->input('alt_text')
        );

        return redirect()->to($this->workspaceRoute('admin.cms.media.index'))->with('alert', [
            'type' => 'success',
            'message' => 'Media ajoute a la bibliotheque CMS.',
        ]);
    }

    private function workspace(): string
    {
        $workspace = request('workspace');

        return in_array($workspace, ['bantudelice', 'kende', 'mema'], true) ? $workspace : 'bantudelice';
    }

    private function workspaceMeta(): array
    {
        return match ($this->workspace()) {
            'kende' => ['key' => 'kende', 'label' => 'Kende', 'eyebrow' => 'CMS Mobilite', 'description' => 'Assets transport, flotte et pages de mobilite.'],
            'mema' => ['key' => 'mema', 'label' => 'Mema', 'eyebrow' => 'CMS Colis', 'description' => 'Assets logistiques, relais et parcours colis.'],
            default => ['key' => 'bantudelice', 'label' => 'BantuDelice', 'eyebrow' => 'CMS Food ops', 'description' => 'Assets food, menus et storefront.'],
        };
    }

    private function workspaceRoute(string $route, array $parameters = []): string
    {
        return route($route, array_merge($parameters, ['workspace' => $this->workspace()]));
    }

    private function usageGuide(): array
    {
        $workspace = $this->workspace();

        if ($workspace === 'bantudelice') {
            return [
                'title' => 'Affectations prioritaires',
                'description' => 'Les assets food servent surtout le storefront, les cartes produits et l accueil BantuDelice.',
                'actions' => [
                    [
                        'label' => 'Media produits',
                        'href' => route('total.pro', ['media_status' => 'missing']),
                        'meta' => 'Backlog catalogue',
                    ],
                    [
                        'label' => 'Accueil storefront',
                        'href' => $this->workspaceRoute('admin.home-content.edit', ['focus' => 'media']),
                        'meta' => 'Hero et opportunites',
                    ],
                ],
                'slots' => [],
            ];
        }

        $content = ConfigService::getHomeContent($workspace) ?: [];
        $slots = collect($workspace === 'kende'
            ? [
                ['key' => 'hero_transport_image', 'label' => 'Hero transport'],
                ['key' => 'opportunity_1_image', 'label' => 'Opportunite 1'],
                ['key' => 'opportunity_2_image', 'label' => 'Opportunite 2'],
                ['key' => 'opportunity_3_image', 'label' => 'Opportunite 3'],
            ]
            : [
                ['key' => 'hero_colis_image', 'label' => 'Hero colis'],
                ['key' => 'opportunity_1_image', 'label' => 'Opportunite 1'],
                ['key' => 'opportunity_2_image', 'label' => 'Opportunite 2'],
                ['key' => 'opportunity_3_image', 'label' => 'Opportunite 3'],
            ])->map(function (array $slot) use ($content) {
                $slot['is_ready'] = filled((string) ($content[$slot['key']] ?? ''));

                return $slot;
            })->values();

        return [
            'title' => 'Affectations prioritaires',
            'description' => $workspace === 'kende'
                ? 'Les assets Kende servent d abord les heroes et cartes d opportunites du parcours transport.'
                : 'Les assets Mema servent d abord le hero colis et les cartes d opportunites de la landing.',
            'actions' => [
                [
                    'label' => 'Media accueil',
                    'href' => $this->workspaceRoute('admin.home-content.edit', ['focus' => 'media']),
                    'meta' => $slots->where('is_ready', true)->count() . '/' . $slots->count() . ' slots prets',
                ],
                [
                    'label' => 'Contenus CMS',
                    'href' => $this->workspaceRoute('admin.cms.contents.index'),
                    'meta' => 'Sections et pages',
                ],
            ],
            'slots' => $slots->all(),
        ];
    }
}
