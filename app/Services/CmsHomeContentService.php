<?php

namespace App\Services;

use App\CmsContent;
use App\CmsContentFieldValue;
use App\CmsContentType;
use Database\Seeders\CmsCoreSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class CmsHomeContentService
{
    private const CONTENT_TYPE_SLUG = 'home_section';

    private const SECTION_KEYS = [
        'hero',
        'restaurants',
        'services',
        'support',
        'popular_products',
        'testimonials',
        'testimonial_one',
        'testimonial_two',
        'testimonial_three',
        'opportunities',
        'opportunity_one',
        'opportunity_two',
        'opportunity_three',
    ];

    public function __construct(private CmsContentService $cmsContentService)
    {
    }

    public function hasCmsSections(?string $workspace = null): bool
    {
        $type = $this->homeSectionType();

        if (!$type) {
            return false;
        }

        return CmsContent::query()
            ->where('content_type_id', $type->id)
            ->whereIn('slug', $this->sectionSlugs($workspace))
            ->exists();
    }

    public function migrateLegacyHomeContentIfNeeded(array $legacyContent, ?int $userId = null, ?string $workspace = null): void
    {
        if ($this->hasCmsSections($workspace)) {
            $this->backfillMissingImageValues($legacyContent, $workspace);
            return;
        }

        $this->persistSections($legacyContent, $userId, false, $workspace);
    }

    public function updateFromRequest(Request $request, ?int $userId = null, ?string $workspace = null): void
    {
        $payload = [
            'hero_badge' => (string) $request->input('home_hero_badge', ''),
            'hero_title_line_1' => (string) $request->input('home_hero_title_line_1', ''),
            'hero_title_line_2' => (string) $request->input('home_hero_title_line_2', ''),
            'hero_description' => (string) $request->input('home_hero_description', ''),
            'restaurants_tag' => (string) $request->input('home_restaurants_tag', ''),
            'restaurants_title' => (string) $request->input('home_restaurants_title', ''),
            'restaurants_subtitle' => (string) $request->input('home_restaurants_subtitle', ''),
            'services_title' => (string) $request->input('home_services_title', ''),
            'services_subtitle' => (string) $request->input('home_services_subtitle', ''),
            'support_title' => (string) $request->input('home_support_title', ''),
            'support_description' => (string) $request->input('home_support_description', ''),
            'support_cta_text' => (string) $request->input('home_support_cta_text', ''),
            'popular_products_tag' => (string) $request->input('home_popular_products_tag', ''),
            'popular_products_title' => (string) $request->input('home_popular_products_title', ''),
            'popular_products_subtitle' => (string) $request->input('home_popular_products_subtitle', ''),
            'testimonials_tag' => (string) $request->input('home_testimonials_tag', ''),
            'testimonials_title' => (string) $request->input('home_testimonials_title', ''),
            'testimonials_subtitle' => (string) $request->input('home_testimonials_subtitle', ''),
            'testimonial_1_tag' => (string) $request->input('home_testimonial_1_tag', ''),
            'testimonial_1_quote' => (string) $request->input('home_testimonial_1_quote', ''),
            'testimonial_1_name' => (string) $request->input('home_testimonial_1_name', ''),
            'testimonial_1_loc' => (string) $request->input('home_testimonial_1_loc', ''),
            'testimonial_2_tag' => (string) $request->input('home_testimonial_2_tag', ''),
            'testimonial_2_quote' => (string) $request->input('home_testimonial_2_quote', ''),
            'testimonial_2_name' => (string) $request->input('home_testimonial_2_name', ''),
            'testimonial_2_loc' => (string) $request->input('home_testimonial_2_loc', ''),
            'testimonial_3_tag' => (string) $request->input('home_testimonial_3_tag', ''),
            'testimonial_3_quote' => (string) $request->input('home_testimonial_3_quote', ''),
            'testimonial_3_name' => (string) $request->input('home_testimonial_3_name', ''),
            'testimonial_3_loc' => (string) $request->input('home_testimonial_3_loc', ''),
            'opportunities_tag' => (string) $request->input('home_opportunities_tag', ''),
            'opportunities_title' => (string) $request->input('home_opportunities_title', ''),
            'opportunities_subtitle' => (string) $request->input('home_opportunities_subtitle', ''),
            'opportunity_1_title' => (string) $request->input('home_opportunity_1_title', ''),
            'opportunity_1_body' => (string) $request->input('home_opportunity_1_body', ''),
            'opportunity_1_cta' => (string) $request->input('home_opportunity_1_cta', ''),
            'opportunity_1_url' => (string) $request->input('home_opportunity_1_url', ''),
            'opportunity_2_title' => (string) $request->input('home_opportunity_2_title', ''),
            'opportunity_2_body' => (string) $request->input('home_opportunity_2_body', ''),
            'opportunity_2_cta' => (string) $request->input('home_opportunity_2_cta', ''),
            'opportunity_2_url' => (string) $request->input('home_opportunity_2_url', ''),
            'opportunity_3_title' => (string) $request->input('home_opportunity_3_title', ''),
            'opportunity_3_body' => (string) $request->input('home_opportunity_3_body', ''),
            'opportunity_3_cta' => (string) $request->input('home_opportunity_3_cta', ''),
            'opportunity_3_url' => (string) $request->input('home_opportunity_3_url', ''),
            'hero_main_image' => $request->file('home_hero_main_image') ?: $request->input('home_hero_main_image_media_path'),
            'hero_colis_image' => $request->file('home_hero_colis_image') ?: $request->input('home_hero_colis_image_media_path'),
            'hero_transport_image' => $request->file('home_hero_transport_image') ?: $request->input('home_hero_transport_image_media_path'),
            'service_food_image' => $request->file('home_service_food_image') ?: $request->input('home_service_food_image_media_path'),
            'service_colis_image' => $request->file('home_service_colis_image') ?: $request->input('home_service_colis_image_media_path'),
            'service_transport_image' => $request->file('home_service_transport_image') ?: $request->input('home_service_transport_image_media_path'),
            'opportunity_1_image' => $request->file('home_opportunity_1_image') ?: $request->input('home_opportunity_1_image_media_path'),
            'opportunity_2_image' => $request->file('home_opportunity_2_image') ?: $request->input('home_opportunity_2_image_media_path'),
            'opportunity_3_image' => $request->file('home_opportunity_3_image') ?: $request->input('home_opportunity_3_image_media_path'),
        ];

        $this->persistSections($payload, $userId, true, $workspace);
    }

    public function syncWorkspaceSeed(array $payload, ?int $userId = null, ?string $workspace = null, bool $preserveExistingImages = true): void
    {
        $this->persistSections($payload, $userId, $preserveExistingImages, $workspace);
    }

    public function getHomeContent(?string $workspace = null): ?array
    {
        $type = $this->homeSectionType();

        if (!$type) {
            return null;
        }

        $sections = CmsContent::query()
            ->with(['values.field'])
            ->where('content_type_id', $type->id)
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->whereIn('slug', $this->sectionSlugs($workspace))
            ->get()
            ->keyBy('slug');

        if ($sections->isEmpty()) {
            return null;
        }

        $hero = $sections->get($this->sectionSlug('hero', $workspace));
        $restaurants = $sections->get($this->sectionSlug('restaurants', $workspace));
        $services = $sections->get($this->sectionSlug('services', $workspace));
        $support = $sections->get($this->sectionSlug('support', $workspace));
        $popularProducts = $sections->get($this->sectionSlug('popular_products', $workspace));
        $testimonials = $sections->get($this->sectionSlug('testimonials', $workspace));
        $testimonialOne = $sections->get($this->sectionSlug('testimonial_one', $workspace));
        $testimonialTwo = $sections->get($this->sectionSlug('testimonial_two', $workspace));
        $testimonialThree = $sections->get($this->sectionSlug('testimonial_three', $workspace));
        $opportunities = $sections->get($this->sectionSlug('opportunities', $workspace));
        $opportunityOne = $sections->get($this->sectionSlug('opportunity_one', $workspace));
        $opportunityTwo = $sections->get($this->sectionSlug('opportunity_two', $workspace));
        $opportunityThree = $sections->get($this->sectionSlug('opportunity_three', $workspace));

        return array_filter([
            'hero_badge' => $this->fieldValue($hero, 'home_section_eyebrow'),
            'hero_title_line_1' => $hero?->title,
            'hero_title_line_2' => $hero?->excerpt,
            'hero_description' => $this->fieldValue($hero, 'home_section_body'),
            'hero_main_image' => $this->fieldValue($hero, 'home_section_image'),
            'hero_colis_image' => $this->fieldValue($hero, 'home_section_image_secondary'),
            'hero_transport_image' => $this->fieldValue($hero, 'home_section_image_tertiary'),
            'restaurants_tag' => $this->fieldValue($restaurants, 'home_section_eyebrow'),
            'restaurants_title' => $restaurants?->title,
            'restaurants_subtitle' => $restaurants?->excerpt,
            'services_title' => $services?->title,
            'services_subtitle' => $services?->excerpt,
            'service_food_image' => $this->fieldValue($services, 'home_section_image'),
            'service_colis_image' => $this->fieldValue($services, 'home_section_image_secondary'),
            'service_transport_image' => $this->fieldValue($services, 'home_section_image_tertiary'),
            'support_title' => $support?->title,
            'support_description' => $this->fieldValue($support, 'home_section_body'),
            'support_cta_text' => $this->fieldValue($support, 'home_section_primary_cta_label'),
            'popular_products_tag' => $this->fieldValue($popularProducts, 'home_section_eyebrow'),
            'popular_products_title' => $popularProducts?->title,
            'popular_products_subtitle' => $popularProducts?->excerpt,
            'testimonials_tag' => $this->fieldValue($testimonials, 'home_section_eyebrow'),
            'testimonials_title' => $testimonials?->title,
            'testimonials_subtitle' => $testimonials?->excerpt,
            'testimonial_1_tag' => $this->fieldValue($testimonialOne, 'home_section_eyebrow'),
            'testimonial_1_quote' => $this->fieldValue($testimonialOne, 'home_section_body'),
            'testimonial_1_name' => $testimonialOne?->title,
            'testimonial_1_loc' => $testimonialOne?->excerpt,
            'testimonial_2_tag' => $this->fieldValue($testimonialTwo, 'home_section_eyebrow'),
            'testimonial_2_quote' => $this->fieldValue($testimonialTwo, 'home_section_body'),
            'testimonial_2_name' => $testimonialTwo?->title,
            'testimonial_2_loc' => $testimonialTwo?->excerpt,
            'testimonial_3_tag' => $this->fieldValue($testimonialThree, 'home_section_eyebrow'),
            'testimonial_3_quote' => $this->fieldValue($testimonialThree, 'home_section_body'),
            'testimonial_3_name' => $testimonialThree?->title,
            'testimonial_3_loc' => $testimonialThree?->excerpt,
            'opportunities_tag' => $this->fieldValue($opportunities, 'home_section_eyebrow'),
            'opportunities_title' => $opportunities?->title,
            'opportunities_subtitle' => $opportunities?->excerpt,
            'opportunity_1_title' => $opportunityOne?->title,
            'opportunity_1_body' => $this->fieldValue($opportunityOne, 'home_section_body'),
            'opportunity_1_cta' => $this->fieldValue($opportunityOne, 'home_section_primary_cta_label'),
            'opportunity_1_url' => $this->fieldValue($opportunityOne, 'home_section_primary_cta_url'),
            'opportunity_1_image' => $this->fieldValue($opportunityOne, 'home_section_image')
                ?: $this->fieldValue($opportunityOne, 'home_section_image_secondary')
                ?: $this->fieldValue($opportunityOne, 'home_section_image_tertiary'),
            'opportunity_2_title' => $opportunityTwo?->title,
            'opportunity_2_body' => $this->fieldValue($opportunityTwo, 'home_section_body'),
            'opportunity_2_cta' => $this->fieldValue($opportunityTwo, 'home_section_primary_cta_label'),
            'opportunity_2_url' => $this->fieldValue($opportunityTwo, 'home_section_primary_cta_url'),
            'opportunity_2_image' => $this->fieldValue($opportunityTwo, 'home_section_image')
                ?: $this->fieldValue($opportunityTwo, 'home_section_image_secondary')
                ?: $this->fieldValue($opportunityTwo, 'home_section_image_tertiary'),
            'opportunity_3_title' => $opportunityThree?->title,
            'opportunity_3_body' => $this->fieldValue($opportunityThree, 'home_section_body'),
            'opportunity_3_cta' => $this->fieldValue($opportunityThree, 'home_section_primary_cta_label'),
            'opportunity_3_url' => $this->fieldValue($opportunityThree, 'home_section_primary_cta_url'),
            'opportunity_3_image' => $this->fieldValue($opportunityThree, 'home_section_image')
                ?: $this->fieldValue($opportunityThree, 'home_section_image_secondary')
                ?: $this->fieldValue($opportunityThree, 'home_section_image_tertiary'),
        ], static fn ($value) => $value !== null && $value !== '');
    }

    public function sectionEditLinks(?string $workspace = null): array
    {
        $type = $this->homeSectionType();

        if (!$type) {
            return [];
        }

        return CmsContent::query()
            ->where('content_type_id', $type->id)
            ->whereIn('slug', $this->sectionSlugs($workspace))
            ->get(['id', 'slug', 'title'])
            ->mapWithKeys(function (CmsContent $content) {
                return [$content->slug => [
                    'id' => $content->id,
                    'title' => $content->title,
                ]];
            })
            ->toArray();
    }

    private function persistSections(array $payload, ?int $userId, bool $preserveExistingImages, ?string $workspace = null): void
    {
        $type = $this->homeSectionType();

        if (!$type) {
            return;
        }

        $fieldsByKey = $type->fields->keyBy('key');

        foreach ($this->sectionDefinitions($payload, $workspace) as $definition) {
            $content = CmsContent::query()
                ->where('content_type_id', $type->id)
                ->where('slug', $definition['slug'])
                ->first();

            $fieldValues = [];
            foreach ($definition['fields'] as $fieldKey => $value) {
                $field = $fieldsByKey->get($fieldKey);
                if (!$field) {
                    continue;
                }

                if ($value === null || $value === '') {
                    if ($preserveExistingImages && $content && str_contains($field->field_type, 'image')) {
                        continue;
                    }
                }

                $fieldValues[$field->id] = $value;
            }

            $contentData = [
                'title' => $definition['title'],
                'slug' => $definition['slug'],
                'status' => 'published',
                'excerpt' => $definition['excerpt'],
                'layout' => 'home_section',
                'seo_title' => null,
                'seo_description' => null,
            ];

            if ($content) {
                $this->cmsContentService->update($content, $contentData, $fieldValues, $userId);
                continue;
            }

            $this->cmsContentService->create($type, $contentData, $fieldValues, $userId);
        }
    }

    private function sectionDefinitions(array $payload, ?string $workspace = null): array
    {
        return [
            [
                'slug' => $this->sectionSlug('hero', $workspace),
                'title' => $payload['hero_title_line_1'] ?? '',
                'excerpt' => $payload['hero_title_line_2'] ?? '',
                'fields' => [
                    'home_section_key' => 'hero',
                    'home_section_eyebrow' => $payload['hero_badge'] ?? '',
                    'home_section_body' => $payload['hero_description'] ?? '',
                    'home_section_image' => $payload['hero_main_image'] ?? null,
                    'home_section_image_secondary' => $payload['hero_colis_image'] ?? null,
                    'home_section_image_tertiary' => $payload['hero_transport_image'] ?? null,
                    'home_section_sort_order' => 10,
                    'home_section_is_active' => 1,
                ],
            ],
            [
                'slug' => $this->sectionSlug('restaurants', $workspace),
                'title' => $payload['restaurants_title'] ?? '',
                'excerpt' => $payload['restaurants_subtitle'] ?? '',
                'fields' => [
                    'home_section_key' => 'restaurants',
                    'home_section_eyebrow' => $payload['restaurants_tag'] ?? '',
                    'home_section_sort_order' => 20,
                    'home_section_is_active' => 1,
                ],
            ],
            [
                'slug' => $this->sectionSlug('services', $workspace),
                'title' => $payload['services_title'] ?? '',
                'excerpt' => $payload['services_subtitle'] ?? '',
                'fields' => [
                    'home_section_key' => 'services',
                    'home_section_image' => $payload['service_food_image'] ?? null,
                    'home_section_image_secondary' => $payload['service_colis_image'] ?? null,
                    'home_section_image_tertiary' => $payload['service_transport_image'] ?? null,
                    'home_section_sort_order' => 30,
                    'home_section_is_active' => 1,
                ],
            ],
            [
                'slug' => $this->sectionSlug('support', $workspace),
                'title' => $payload['support_title'] ?? '',
                'excerpt' => null,
                'fields' => [
                    'home_section_key' => 'support',
                    'home_section_body' => $payload['support_description'] ?? '',
                    'home_section_primary_cta_label' => $payload['support_cta_text'] ?? '',
                    'home_section_primary_cta_url' => route('contact.us'),
                    'home_section_sort_order' => 40,
                    'home_section_is_active' => 1,
                ],
            ],
            [
                'slug' => $this->sectionSlug('popular_products', $workspace),
                'title' => $payload['popular_products_title'] ?? '',
                'excerpt' => $payload['popular_products_subtitle'] ?? '',
                'fields' => [
                    'home_section_key' => 'popular_products',
                    'home_section_eyebrow' => $payload['popular_products_tag'] ?? '',
                    'home_section_sort_order' => 50,
                    'home_section_is_active' => 1,
                ],
            ],
            [
                'slug' => $this->sectionSlug('testimonials', $workspace),
                'title' => $payload['testimonials_title'] ?? '',
                'excerpt' => $payload['testimonials_subtitle'] ?? '',
                'fields' => [
                    'home_section_key' => 'testimonials',
                    'home_section_eyebrow' => $payload['testimonials_tag'] ?? '',
                    'home_section_sort_order' => 60,
                    'home_section_is_active' => 1,
                ],
            ],
            [
                'slug' => $this->sectionSlug('testimonial_one', $workspace),
                'title' => $payload['testimonial_1_name'] ?? '',
                'excerpt' => $payload['testimonial_1_loc'] ?? '',
                'fields' => [
                    'home_section_key' => 'testimonial_one',
                    'home_section_eyebrow' => $payload['testimonial_1_tag'] ?? '',
                    'home_section_body' => $payload['testimonial_1_quote'] ?? '',
                    'home_section_sort_order' => 61,
                    'home_section_is_active' => 1,
                ],
            ],
            [
                'slug' => $this->sectionSlug('testimonial_two', $workspace),
                'title' => $payload['testimonial_2_name'] ?? '',
                'excerpt' => $payload['testimonial_2_loc'] ?? '',
                'fields' => [
                    'home_section_key' => 'testimonial_two',
                    'home_section_eyebrow' => $payload['testimonial_2_tag'] ?? '',
                    'home_section_body' => $payload['testimonial_2_quote'] ?? '',
                    'home_section_sort_order' => 62,
                    'home_section_is_active' => 1,
                ],
            ],
            [
                'slug' => $this->sectionSlug('testimonial_three', $workspace),
                'title' => $payload['testimonial_3_name'] ?? '',
                'excerpt' => $payload['testimonial_3_loc'] ?? '',
                'fields' => [
                    'home_section_key' => 'testimonial_three',
                    'home_section_eyebrow' => $payload['testimonial_3_tag'] ?? '',
                    'home_section_body' => $payload['testimonial_3_quote'] ?? '',
                    'home_section_sort_order' => 63,
                    'home_section_is_active' => 1,
                ],
            ],
            [
                'slug' => $this->sectionSlug('opportunities', $workspace),
                'title' => $payload['opportunities_title'] ?? '',
                'excerpt' => $payload['opportunities_subtitle'] ?? '',
                'fields' => [
                    'home_section_key' => 'opportunities',
                    'home_section_eyebrow' => $payload['opportunities_tag'] ?? '',
                    'home_section_sort_order' => 70,
                    'home_section_is_active' => 1,
                ],
            ],
            [
                'slug' => $this->sectionSlug('opportunity_one', $workspace),
                'title' => $payload['opportunity_1_title'] ?? '',
                'excerpt' => null,
                'fields' => [
                    'home_section_key' => 'opportunity_one',
                    'home_section_body' => $payload['opportunity_1_body'] ?? '',
                    'home_section_image' => $payload['opportunity_1_image'] ?? null,
                    'home_section_primary_cta_label' => $payload['opportunity_1_cta'] ?? '',
                    'home_section_primary_cta_url' => $payload['opportunity_1_url'] ?? '',
                    'home_section_sort_order' => 71,
                    'home_section_is_active' => 1,
                ],
            ],
            [
                'slug' => $this->sectionSlug('opportunity_two', $workspace),
                'title' => $payload['opportunity_2_title'] ?? '',
                'excerpt' => null,
                'fields' => [
                    'home_section_key' => 'opportunity_two',
                    'home_section_body' => $payload['opportunity_2_body'] ?? '',
                    'home_section_image' => $payload['opportunity_2_image'] ?? null,
                    'home_section_primary_cta_label' => $payload['opportunity_2_cta'] ?? '',
                    'home_section_primary_cta_url' => $payload['opportunity_2_url'] ?? '',
                    'home_section_sort_order' => 72,
                    'home_section_is_active' => 1,
                ],
            ],
            [
                'slug' => $this->sectionSlug('opportunity_three', $workspace),
                'title' => $payload['opportunity_3_title'] ?? '',
                'excerpt' => null,
                'fields' => [
                    'home_section_key' => 'opportunity_three',
                    'home_section_body' => $payload['opportunity_3_body'] ?? '',
                    'home_section_image' => $payload['opportunity_3_image'] ?? null,
                    'home_section_primary_cta_label' => $payload['opportunity_3_cta'] ?? '',
                    'home_section_primary_cta_url' => $payload['opportunity_3_url'] ?? '',
                    'home_section_sort_order' => 73,
                    'home_section_is_active' => 1,
                ],
            ],
        ];
    }

    private function fieldValue(?CmsContent $content, string $fieldKey): ?string
    {
        if (!$content) {
            return null;
        }

        static $cache = [];
        $cacheKey = $content->id . ':' . $fieldKey;

        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $value = $content->values->first(function ($item) use ($fieldKey) {
            return optional($item->field)->key === $fieldKey;
        });

        return $cache[$cacheKey] = $value?->value;
    }

    private function backfillMissingImageValues(array $payload, ?string $workspace = null): void
    {
        $type = $this->homeSectionType();

        if (!$type) {
            return;
        }

        $fieldsByKey = $type->fields->keyBy('key');

        foreach ($this->sectionDefinitions($payload, $workspace) as $definition) {
            $content = CmsContent::query()
                ->with(['values.field'])
                ->where('content_type_id', $type->id)
                ->where('slug', $definition['slug'])
                ->first();

            if (!$content) {
                continue;
            }

            foreach ($definition['fields'] as $fieldKey => $value) {
                if (blank($value)) {
                    continue;
                }

                $field = $fieldsByKey->get($fieldKey);

                if (!$field || $field->field_type !== 'image') {
                    continue;
                }

                if (filled($this->fieldValue($content, $fieldKey))) {
                    continue;
                }

                CmsContentFieldValue::updateOrCreate(
                    [
                        'content_id' => $content->id,
                        'content_field_id' => $field->id,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }
        }
    }

    private function homeSectionType(): ?CmsContentType
    {
        $query = CmsContentType::query()
            ->with('fields')
            ->where('slug', self::CONTENT_TYPE_SLUG)
            ->where('is_active', true);

        $type = $query->first();

        if (
            $type
            || !Schema::hasTable('cms_content_types')
            || !Schema::hasTable('cms_content_fields')
        ) {
            return $type;
        }

        app(CmsCoreSeeder::class)->run();

        return $query->first();
    }

    private function sectionSlugs(?string $workspace = null): array
    {
        return array_map(fn (string $key) => $this->sectionSlug($key, $workspace), self::SECTION_KEYS);
    }

    private function sectionSlug(string $key, ?string $workspace = null): string
    {
        $workspace = $this->resolveWorkspace($workspace);
        $baseSlug = 'home-' . str_replace('_', '-', $key);

        if ($workspace === 'bantudelice') {
            return $baseSlug;
        }

        return $workspace . '-' . $baseSlug;
    }

    private function resolveWorkspace(?string $workspace = null): string
    {
        $workspace = $workspace ?: request('workspace');

        return in_array($workspace, ['bantudelice', 'kende', 'mema'], true) ? $workspace : 'bantudelice';
    }
}
