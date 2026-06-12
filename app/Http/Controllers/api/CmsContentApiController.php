<?php

namespace App\Http\Controllers\api;

use App\CmsContent;
use App\CmsContentType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CmsContentApiController extends Controller
{
    public function index(Request $request)
    {
        $query = CmsContent::query()
            ->with(['contentType', 'values.field'])
            ->where('status', 'published')
            ->where(function ($subQuery) {
                $subQuery->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->latest('published_at');

        if ($request->filled('type')) {
            $type = CmsContentType::query()
                ->where('slug', $request->input('type'))
                ->orWhere('id', $request->input('type'))
                ->first();

            if ($type) {
                $query->where('content_type_id', $type->id);
            }
        }

        if ($request->filled('slug')) {
            $query->where('slug', $request->input('slug'));
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $limit = min(max((int) $request->input('limit', 20), 1), 100);

        return response()->json([
            'data' => $query->paginate($limit)->through(fn (CmsContent $content) => $this->transform($content)),
        ]);
    }

    public function show(string $slug)
    {
        $content = CmsContent::query()
            ->with(['contentType', 'values.field'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->firstOrFail();

        return response()->json([
            'data' => $this->transform($content),
        ]);
    }

    private function transform(CmsContent $content): array
    {
        return [
            'id' => $content->id,
            'title' => $content->title,
            'slug' => $content->slug,
            'status' => $content->status,
            'excerpt' => $content->excerpt,
            'layout' => $content->layout,
            'seo_title' => $content->seo_title,
            'seo_description' => $content->seo_description,
            'published_at' => optional($content->published_at)?->toIso8601String(),
            'type' => [
                'id' => optional($content->contentType)->id,
                'name' => optional($content->contentType)->name,
                'slug' => optional($content->contentType)->slug,
            ],
            'fields' => $content->values->mapWithKeys(function ($value) {
                return [optional($value->field)->key => $value->value];
            })->toArray(),
        ];
    }
}
