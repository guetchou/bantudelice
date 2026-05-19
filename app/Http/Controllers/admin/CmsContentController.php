<?php

namespace App\Http\Controllers\admin;

use App\CmsContent;
use App\CmsContentType;
use App\User;
use App\Http\Controllers\Controller;
use App\Services\CmsAccessService;
use App\Services\CmsContentService;
use App\Services\ConfigService;
use Illuminate\Http\Request;

class CmsContentController extends Controller
{
    public function __construct(
        private CmsContentService $cmsContentService,
        private CmsAccessService $cmsAccessService
    )
    {
    }

    public function index(Request $request)
    {
        $this->cmsAccessService->authorize(auth()->user(), 'view');
        $types = CmsContentType::where('is_active', true)->orderBy('name')->get();
        $authors = User::query()
            ->whereIn('type', ['admin', 'editor', 'editeur', 'author', 'auteur', 'contributor', 'contributeur'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $query = CmsContent::with(['contentType', 'author', 'editor'])->latest('id');

        if ($request->filled('type')) {
            $query->where('content_type_id', $request->integer('type'));
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'scheduled') {
                $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '>', now());
            } else {
                $query->where('status', $request->input('status'));
            }
        }

        if ($request->filled('author')) {
            $query->where('author_id', $request->integer('author'));
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        if ($request->filled('published_from')) {
            $query->whereDate('published_at', '>=', $request->input('published_from'));
        }

        if ($request->filled('published_to')) {
            $query->whereDate('published_at', '<=', $request->input('published_to'));
        }

        $contents = $query->paginate(20);

        return view('admin.cms.contents.index')->with(compact('contents', 'types', 'authors'))->with('cmsWorkspace', $this->workspaceMeta());
    }

    public function create(Request $request)
    {
        $this->cmsAccessService->authorize(auth()->user(), 'create');
        $types = CmsContentType::with('fields')->where('is_active', true)->orderBy('name')->get();
        if ($types->isEmpty()) {
            return redirect()->to($this->workspaceRoute('admin.cms.content-types.index'))->with('alert', [
                'type' => 'warning',
                'message' => 'Créez d’abord un type de contenu CMS avant d’ajouter un contenu.',
            ]);
        }

        $selectedType = $types->firstWhere('id', $request->integer('content_type_id')) ?? $types->first();
        $content = new CmsContent(['status' => 'draft']);

        return view('admin.cms.contents.form')->with([
            'content' => $content,
            'types' => $types,
            'selectedType' => $selectedType,
            'fieldValues' => [],
            'cmsWorkspace' => $this->workspaceMeta(),
        ]);
    }

    public function store(Request $request)
    {
        $this->cmsAccessService->authorize(auth()->user(), 'create');
        $type = CmsContentType::with('fields')->findOrFail($request->integer('content_type_id'));
        $request->validate($this->cmsContentService->validationRules($type));

        $fieldValues = $request->input('fields', []);
        foreach ($request->allFiles()['fields'] ?? [] as $fieldId => $uploadedFile) {
            $fieldValues[$fieldId] = $uploadedFile;
        }

        $content = $this->cmsContentService->create(
            $type,
            $request->only(['title', 'slug', 'status', 'excerpt', 'layout', 'seo_title', 'seo_description', 'published_at', 'revision_note']),
            $fieldValues,
            optional(auth()->user())->id
        );

        $this->clearHomeContentCacheIfNeeded($content);

        return redirect()->to($this->workspaceRoute('admin.cms.contents.edit', ['content' => $content]))->with('alert', [
            'type' => 'success',
            'message' => 'Contenu créé avec succès.',
        ]);
    }

    public function edit(CmsContent $content)
    {
        $this->cmsAccessService->authorize(auth()->user(), 'view', $content);
        $content->load(['contentType.fields', 'revisions', 'statusLogs.actor']);
        $types = CmsContentType::with('fields')->where('is_active', true)->orderBy('name')->get();

        return view('admin.cms.contents.form')->with([
            'content' => $content,
            'types' => $types,
            'selectedType' => $content->contentType,
            'fieldValues' => $this->cmsContentService->fieldValueMap($content),
            'cmsWorkspace' => $this->workspaceMeta(),
        ]);
    }

    public function update(Request $request, CmsContent $content)
    {
        $this->cmsAccessService->authorize(auth()->user(), 'update', $content);
        $content->load('contentType.fields');
        $type = $content->contentType;
        $request->validate($this->cmsContentService->validationRules($type, true));

        $fieldValues = $request->input('fields', []);
        foreach ($request->allFiles()['fields'] ?? [] as $fieldId => $uploadedFile) {
            $fieldValues[$fieldId] = $uploadedFile;
        }

        $this->cmsContentService->update(
            $content,
            $request->only(['title', 'slug', 'status', 'excerpt', 'layout', 'seo_title', 'seo_description', 'published_at', 'revision_note']),
            $fieldValues,
            optional(auth()->user())->id
        );

        $this->clearHomeContentCacheIfNeeded($content);

        return redirect()->to($this->workspaceRoute('admin.cms.contents.edit', ['content' => $content]))->with('alert', [
            'type' => 'success',
            'message' => 'Contenu mis à jour.',
        ]);
    }

    public function transition(Request $request, CmsContent $content, string $toStatus)
    {
        $ability = match ($toStatus) {
            'pending_review' => 'submit_review',
            'published' => 'publish',
            'archived' => 'archive',
            'draft' => 'update',
            default => 'update',
        };

        $this->cmsAccessService->authorize(auth()->user(), $ability, $content);

        $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $this->cmsContentService->transition(
            $content,
            $toStatus,
            optional(auth()->user())->id,
            $request->input('note')
        );

        $this->clearHomeContentCacheIfNeeded($content);

        return redirect()->to($this->workspaceRoute('admin.cms.contents.edit', ['content' => $content]))->with('alert', [
            'type' => 'success',
            'message' => 'Statut editorial mis a jour.',
        ]);
    }

    public function destroy(CmsContent $content)
    {
        $this->cmsAccessService->authorize(auth()->user(), 'destroy', $content);

        $this->cmsContentService->destroy($content);
        $this->clearHomeContentCacheIfNeeded($content);

        return redirect()->to($this->workspaceRoute('admin.cms.contents.index'))->with('alert', [
            'type' => 'success',
            'message' => 'Contenu supprime avec succes.',
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
                'eyebrow' => 'CMS Mobilite',
                'description' => 'Editez les contenus transport, trajets, flotte et pages de mobilite.',
            ],
            'mema' => [
                'key' => 'mema',
                'label' => 'Mema',
                'eyebrow' => 'CMS Colis',
                'description' => 'Editez les contenus logistiques, relais, suivi et parcours colis.',
            ],
            default => [
                'key' => 'bantudelice',
                'label' => 'BantuDelice',
                'eyebrow' => 'CMS Food ops',
                'description' => 'Editez les contenus food, restaurants, commandes et storefront.',
            ],
        };
    }

    private function workspaceRoute(string $route, array $parameters = []): string
    {
        return route($route, array_merge($parameters, ['workspace' => $this->workspace()]));
    }

    private function clearHomeContentCacheIfNeeded(CmsContent $content): void
    {
        if (optional($content->contentType)->slug === 'home_section') {
            ConfigService::clearHomeContentCache($this->workspace());
        }
    }
}
