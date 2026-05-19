<?php

namespace App\Http\Controllers\admin;

use App\CmsContent;
use App\CmsContentType;
use App\Driver;
use App\Http\Controllers\Controller;
use App\News;
use App\Services\CmsContentService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    public function __construct(private CmsContentService $cmsContentService)
    {
    }

    public function notification($body, $title, $device_token, $key, $click_action)
    {
        if (empty($device_token)) {
            return response()->json(['data' => 'no device token', 'action' => null], 200);
        }

        if (!NotificationService::hasConfiguredFcmKey('driver')) {
            return response()->json(['data' => 'fcm key missing', 'action' => null], 200);
        }

        $result = NotificationService::sendToMultipleDevices(
            $device_token,
            $title,
            $body,
            $key,
            null,
            'driver'
        );

        return response()->json([
            'data' => !empty($result['success']) ? 'notification sent' : 'notification failed',
            'action' => $result['action'] ?? null,
            'result' => $result,
            'click_action' => $click_action,
        ], 200);
    }

    public function index()
    {
        $this->migrateLegacyNewsIfNeeded();

        $news = $this->newsQuery()->get()->map(fn (CmsContent $content) => $this->presentNewsContent($content));

        return view('admin.news.index')->with('news', $news);
    }

    public function create()
    {
        $this->ensureNewsTypeExists();

        $news = $this->presentNewsContent(new CmsContent([
            'title' => '',
            'status' => 'draft',
        ]));

        return view('admin.news.create')->with('news', $news);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:191',
            'description' => 'required|string',
        ]);

        $type = $this->ensureNewsTypeExists();
        $content = $this->cmsContentService->create(
            $type,
            [
                'title' => $request->input('title'),
                'slug' => $this->uniqueNewsSlug($request->input('title')),
                'status' => 'published',
                'excerpt' => Str::limit(strip_tags($request->input('description')), 180),
                'layout' => 'news',
                'seo_title' => $request->input('title'),
                'seo_description' => Str::limit(strip_tags($request->input('description')), 160),
            ],
            $this->newsFieldValues($type, $request->input('description')),
            optional(auth()->user())->id
        );

        return redirect()->route('news.edit', $content->id)->with('alert', [
            'type' => 'success',
            'message' => 'Actualité créée avec succès',
        ]);
    }

    public function show($id)
    {
        abort(404);
    }

    public function edit($id)
    {
        $this->migrateLegacyNewsIfNeeded();

        $news = $this->presentNewsContent($this->findNewsContentOrFail($id));

        return view('admin.news.edit')->with('news', $news);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:191',
            'description' => 'required|string',
        ]);

        $content = $this->findNewsContentOrFail($id);
        $content->load('contentType.fields', 'values.field');

        $this->cmsContentService->update(
            $content,
            [
                'title' => $request->input('title'),
                'slug' => $content->slug ?: $this->uniqueNewsSlug($request->input('title')),
                'status' => 'published',
                'excerpt' => Str::limit(strip_tags($request->input('description')), 180),
                'layout' => 'news',
                'seo_title' => $request->input('title'),
                'seo_description' => Str::limit(strip_tags($request->input('description')), 160),
            ],
            $this->newsFieldValues($content->contentType, $request->input('description')),
            optional(auth()->user())->id
        );

        return redirect()->route('news.index')->with('alert', [
            'type' => 'success',
            'message' => 'Actualité mise à jour avec succès',
        ]);
    }

    public function destroy($id)
    {
        $content = $this->findNewsContentOrFail($id);
        $content->delete();

        return redirect()->route('news.index')->with('alert', [
            'type' => 'success',
            'message' => 'Actualité supprimée avec succès',
        ]);
    }

    public function sentNotification($id)
    {
        $content = $this->findNewsContentOrFail($id);
        $drivers = Driver::query()->get();
        $deviceToken = $drivers->pluck('device_token')->filter()->values()->toArray();

        $body = $this->bodyFromContent($content);
        $title = $content->title;
        $key = 'news';
        $clickAction = 'news_activity';
        $data = $this->notification($body, $title, $deviceToken, $key, $clickAction);

        $alert = [
            'type' => 'success',
            'message' => 'Actualité envoyée avec succès',
        ];

        if (method_exists($data, 'getData')) {
            $response = $data->getData(true);
            if (($response['data'] ?? null) === 'fcm key missing') {
                $alert['type'] = 'warning';
                $alert['message'] = 'Clé FCM absente. Notification non envoyée.';
            }
        }

        return redirect()->back()->with('alert', $alert);
    }

    private function migrateLegacyNewsIfNeeded(): void
    {
        $type = $this->ensureNewsTypeExists();

        if (!Schema::hasTable('news')) {
            return;
        }

        $legacyItems = News::query()->orderBy('id')->get();
        if ($legacyItems->isEmpty()) {
            return;
        }

        foreach ($legacyItems as $legacyItem) {
            $slug = 'legacy-news-' . $legacyItem->id;
            $existing = CmsContent::query()
                ->where('content_type_id', $type->id)
                ->where('slug', $slug)
                ->first();

            if ($existing) {
                continue;
            }

            $this->cmsContentService->create(
                $type,
                [
                    'title' => $legacyItem->title,
                    'slug' => $slug,
                    'status' => 'published',
                    'excerpt' => Str::limit(strip_tags((string) $legacyItem->description), 180),
                    'layout' => 'news',
                    'seo_title' => $legacyItem->title,
                    'seo_description' => Str::limit(strip_tags((string) $legacyItem->description), 160),
                    'created_at' => $legacyItem->created_at,
                    'updated_at' => $legacyItem->updated_at,
                ],
                $this->newsFieldValues($type, (string) $legacyItem->description),
                optional(auth()->user())->id
            );
        }
    }

    private function ensureNewsTypeExists(): CmsContentType
    {
        $type = CmsContentType::query()->with('fields')->where('slug', 'news')->first();

        abort_if(!$type, 500, 'Le type de contenu CMS "news" est introuvable.');

        return $type;
    }

    private function newsQuery()
    {
        $type = $this->ensureNewsTypeExists();

        return CmsContent::query()
            ->with(['values.field'])
            ->where('content_type_id', $type->id)
            ->latest('id');
    }

    private function findNewsContentOrFail($id): CmsContent
    {
        return $this->newsQuery()->findOrFail($id);
    }

    private function bodyFromContent(CmsContent $content): string
    {
        $content->loadMissing('values.field');

        $value = $content->values->first(function ($item) {
            return optional($item->field)->key === 'news_body';
        });

        return (string) ($value->value ?? $content->excerpt ?? '');
    }

    private function presentNewsContent(CmsContent $content): CmsContent
    {
        $content->setAttribute('description', $this->bodyFromContent($content));

        return $content;
    }

    private function newsFieldValues(CmsContentType $type, string $description): array
    {
        $field = $type->fields->firstWhere('key', 'news_body');

        return $field ? [$field->id => $description] : [];
    }

    private function uniqueNewsSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'actualite';
        $slug = $base;
        $counter = 2;

        $type = $this->ensureNewsTypeExists();

        while (CmsContent::query()->where('content_type_id', $type->id)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
