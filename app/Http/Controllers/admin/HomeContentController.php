<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\CmsHomeContentService;
use App\Services\ConfigService;
use App\Services\UnifiedMediaLibraryService;
use Illuminate\Http\Request;

class HomeContentController extends Controller
{
    public function __construct(
        private CmsHomeContentService $cmsHomeContentService,
        private UnifiedMediaLibraryService $unifiedMediaLibraryService
    )
    {
    }

    public function edit()
    {
        $workspace = $this->workspace();
        $this->cmsHomeContentService->migrateLegacyHomeContentIfNeeded(
            ConfigService::getHomeContentDefaults($workspace),
            optional(auth()->user())->id,
            $workspace
        );

        $content = ConfigService::getHomeContent($workspace);
        $cmsSections = $this->cmsHomeContentService->sectionEditLinks($workspace);
        $mediaLibraryOptions = $this->unifiedMediaLibraryService->groupedOptions();
        $mediaBacklog = $this->mediaBacklog($workspace, $content ?? []);

        return view('admin.home_content.edit', compact('content', 'cmsSections', 'mediaLibraryOptions', 'mediaBacklog'))
            ->with('cmsWorkspace', $this->workspaceMeta());
    }

    public function update(Request $request)
    {
        $request->validate([
            'home_hero_badge' => 'nullable|string|max:191',
            'home_hero_title_line_1' => 'nullable|string|max:191',
            'home_hero_title_line_2' => 'nullable|string|max:191',
            'home_hero_description' => 'nullable|string|max:1000',
            'home_restaurants_tag' => 'nullable|string|max:191',
            'home_restaurants_title' => 'nullable|string|max:191',
            'home_restaurants_subtitle' => 'nullable|string|max:500',
            'home_services_title' => 'nullable|string|max:191',
            'home_services_subtitle' => 'nullable|string|max:1000',
            'home_support_title' => 'nullable|string|max:191',
            'home_support_description' => 'nullable|string|max:1000',
            'home_support_cta_text' => 'nullable|string|max:191',
            'home_popular_products_tag' => 'nullable|string|max:191',
            'home_popular_products_title' => 'nullable|string|max:191',
            'home_popular_products_subtitle' => 'nullable|string|max:500',
            'home_testimonials_tag' => 'nullable|string|max:191',
            'home_testimonials_title' => 'nullable|string|max:191',
            'home_testimonials_subtitle' => 'nullable|string|max:500',
            'home_testimonial_1_tag' => 'nullable|string|max:191',
            'home_testimonial_1_quote' => 'nullable|string|max:1000',
            'home_testimonial_1_name' => 'nullable|string|max:191',
            'home_testimonial_1_loc' => 'nullable|string|max:191',
            'home_testimonial_2_tag' => 'nullable|string|max:191',
            'home_testimonial_2_quote' => 'nullable|string|max:1000',
            'home_testimonial_2_name' => 'nullable|string|max:191',
            'home_testimonial_2_loc' => 'nullable|string|max:191',
            'home_testimonial_3_tag' => 'nullable|string|max:191',
            'home_testimonial_3_quote' => 'nullable|string|max:1000',
            'home_testimonial_3_name' => 'nullable|string|max:191',
            'home_testimonial_3_loc' => 'nullable|string|max:191',
            'home_opportunities_tag' => 'nullable|string|max:191',
            'home_opportunities_title' => 'nullable|string|max:191',
            'home_opportunities_subtitle' => 'nullable|string|max:1000',
            'home_opportunity_1_title' => 'nullable|string|max:191',
            'home_opportunity_1_body' => 'nullable|string|max:1000',
            'home_opportunity_1_cta' => 'nullable|string|max:191',
            'home_opportunity_1_url' => 'nullable|string|max:500',
            'home_opportunity_2_title' => 'nullable|string|max:191',
            'home_opportunity_2_body' => 'nullable|string|max:1000',
            'home_opportunity_2_cta' => 'nullable|string|max:191',
            'home_opportunity_2_url' => 'nullable|string|max:500',
            'home_opportunity_3_title' => 'nullable|string|max:191',
            'home_opportunity_3_body' => 'nullable|string|max:1000',
            'home_opportunity_3_cta' => 'nullable|string|max:191',
            'home_opportunity_3_url' => 'nullable|string|max:500',
            'home_hero_main_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'home_hero_main_image_media_path' => 'nullable|string|max:2048',
            'home_hero_colis_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'home_hero_colis_image_media_path' => 'nullable|string|max:2048',
            'home_hero_transport_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'home_hero_transport_image_media_path' => 'nullable|string|max:2048',
            'home_service_food_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'home_service_food_image_media_path' => 'nullable|string|max:2048',
            'home_service_colis_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'home_service_colis_image_media_path' => 'nullable|string|max:2048',
            'home_service_transport_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'home_service_transport_image_media_path' => 'nullable|string|max:2048',
            'home_opportunity_1_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'home_opportunity_1_image_media_path' => 'nullable|string|max:2048',
            'home_opportunity_2_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'home_opportunity_2_image_media_path' => 'nullable|string|max:2048',
            'home_opportunity_3_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'home_opportunity_3_image_media_path' => 'nullable|string|max:2048',
        ]);

        $workspace = $this->workspace();
        $this->cmsHomeContentService->updateFromRequest($request, optional(auth()->user())->id, $workspace);

        ConfigService::clearHomeContentCache($workspace);

        return redirect()->to($this->workspaceRoute('admin.home-content.edit'))->with('alert', [
            'type' => 'success',
            'message' => 'Le contenu de l’accueil a été migré et mis à jour dans le CMS.',
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
            'kende' => [
                'key' => 'kende',
                'label' => 'Kende',
                'eyebrow' => 'Accueil transport',
                'description' => 'Pilotez le hero, les sections trajets, flotte et conversion transport.',
            ],
            'mema' => [
                'key' => 'mema',
                'label' => 'Mema',
                'eyebrow' => 'Accueil colis',
                'description' => 'Pilotez le hero, les sections logistiques, relais et parcours colis.',
            ],
            default => [
                'key' => 'bantudelice',
                'label' => 'BantuDelice',
                'eyebrow' => 'Accueil food',
                'description' => 'Pilotez le hero, les sections restaurants, plats et storefront food.',
            ],
        };
    }

    private function workspaceRoute(string $route, array $parameters = []): string
    {
        return route($route, array_merge($parameters, ['workspace' => $this->workspace()]));
    }

    private function mediaBacklog(string $workspace, array $content): array
    {
        $slots = collect(match ($workspace) {
            'kende' => [
                [
                    'key' => 'hero_transport_image',
                    'label' => 'Hero transport',
                    'usage' => 'Visuel principal sur les pages Kende.',
                    'section_id' => 'home-content-media-hero',
                ],
                [
                    'key' => 'opportunity_1_image',
                    'label' => 'Opportunite 1',
                    'usage' => 'Carte d opportunite Kende.',
                    'section_id' => 'home-content-media-opportunities',
                ],
                [
                    'key' => 'opportunity_2_image',
                    'label' => 'Opportunite 2',
                    'usage' => 'Carte d opportunite Kende.',
                    'section_id' => 'home-content-media-opportunities',
                ],
                [
                    'key' => 'opportunity_3_image',
                    'label' => 'Opportunite 3',
                    'usage' => 'Carte d opportunite Kende.',
                    'section_id' => 'home-content-media-opportunities',
                ],
            ],
            'mema' => [
                [
                    'key' => 'hero_colis_image',
                    'label' => 'Hero colis',
                    'usage' => 'Visuel principal sur la landing Mema.',
                    'section_id' => 'home-content-media-hero',
                ],
                [
                    'key' => 'opportunity_1_image',
                    'label' => 'Opportunite 1',
                    'usage' => 'Carte d opportunite Mema.',
                    'section_id' => 'home-content-media-opportunities',
                ],
                [
                    'key' => 'opportunity_2_image',
                    'label' => 'Opportunite 2',
                    'usage' => 'Carte d opportunite Mema.',
                    'section_id' => 'home-content-media-opportunities',
                ],
                [
                    'key' => 'opportunity_3_image',
                    'label' => 'Opportunite 3',
                    'usage' => 'Carte d opportunite Mema.',
                    'section_id' => 'home-content-media-opportunities',
                ],
            ],
            default => [],
        })->map(function (array $slot) use ($content) {
            $slot['is_ready'] = filled((string) ($content[$slot['key']] ?? ''));

            return $slot;
        })->values();

        return [
            'title' => $workspace === 'kende' ? 'Backlog media Kende' : 'Backlog media Mema',
            'description' => $workspace === 'kende'
                ? 'Ces visuels alimentent directement les pages publiques Kende branchees sur le CMS.'
                : 'Ces visuels alimentent directement la landing Mema branchee sur le CMS.',
            'ready_count' => $slots->where('is_ready', true)->count(),
            'missing_count' => $slots->where('is_ready', false)->count(),
            'slots' => $slots->all(),
        ];
    }
}
