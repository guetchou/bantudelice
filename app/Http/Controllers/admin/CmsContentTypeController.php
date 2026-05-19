<?php

namespace App\Http\Controllers\admin;

use App\CmsContentType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CmsContentTypeController extends Controller
{
    public function index()
    {
        $types = CmsContentType::withCount(['fields', 'contents'])->orderBy('name')->get();
        return view('admin.cms.content_types.index')->with('types', $types)->with('cmsWorkspace', $this->workspaceMeta());
    }

    public function create()
    {
        $type = new CmsContentType(['is_active' => true, 'supports_revisions' => true]);
        return view('admin.cms.content_types.form')->with(compact('type'))->with('cmsWorkspace', $this->workspaceMeta());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'slug' => 'nullable|string|max:191|unique:cms_content_types,slug',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'supports_revisions' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['supports_revisions'] = $request->boolean('supports_revisions', true);

        $type = CmsContentType::create($data);

        return redirect()->to($this->workspaceRoute('admin.cms.content-types.edit', ['contentType' => $type]))->with('alert', [
            'type' => 'success',
            'message' => 'Type de contenu créé avec succès.',
        ]);
    }

    public function edit(CmsContentType $contentType)
    {
        $contentType->loadCount(['fields', 'contents']);
        $contentType->load('fields');
        return view('admin.cms.content_types.form')->with('type', $contentType)->with('cmsWorkspace', $this->workspaceMeta());
    }

    public function update(Request $request, CmsContentType $contentType)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'slug' => 'nullable|string|max:191|unique:cms_content_types,slug,' . $contentType->id,
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'supports_revisions' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['supports_revisions'] = $request->boolean('supports_revisions', true);

        $contentType->update($data);

        return redirect()->to($this->workspaceRoute('admin.cms.content-types.edit', ['contentType' => $contentType]))->with('alert', [
            'type' => 'success',
            'message' => 'Type de contenu mis à jour.',
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
            'kende' => ['key' => 'kende', 'label' => 'Kende', 'eyebrow' => 'CMS Mobilite', 'description' => 'Schemas editoriaux pour transport et mobilite.'],
            'mema' => ['key' => 'mema', 'label' => 'Mema', 'eyebrow' => 'CMS Colis', 'description' => 'Schemas editoriaux pour logistique et expedition.'],
            default => ['key' => 'bantudelice', 'label' => 'BantuDelice', 'eyebrow' => 'CMS Food ops', 'description' => 'Schemas editoriaux pour food et storefront.'],
        };
    }

    private function workspaceRoute(string $route, array $parameters = []): string
    {
        return route($route, array_merge($parameters, ['workspace' => $this->workspace()]));
    }
}
