<?php

namespace App\Http\Controllers\admin;

use App\CmsContentField;
use App\CmsContentType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CmsContentFieldController extends Controller
{
    public function create(CmsContentType $contentType)
    {
        $field = new CmsContentField(['field_type' => 'text', 'sort_order' => ($contentType->fields()->max('sort_order') ?? 0) + 1]);
        return view('admin.cms.content_types.field_form')->with(compact('contentType', 'field'))->with('cmsWorkspace', $this->workspaceMeta());
    }

    public function store(Request $request, CmsContentType $contentType)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'key' => 'nullable|string|max:191|unique:cms_content_fields,key',
            'field_type' => 'required|in:text,textarea,richtext,image,number,boolean,date,datetime,url,json',
            'sort_order' => 'nullable|integer|min:0',
            'default_value' => 'nullable|string',
            'help_text' => 'nullable|string',
            'options' => 'nullable|string',
            'is_required' => 'nullable|boolean',
        ]);

        $data['content_type_id'] = $contentType->id;
        $data['is_required'] = $request->boolean('is_required');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        CmsContentField::create($data);

        return redirect()->to($this->workspaceRoute('admin.cms.content-types.edit', ['contentType' => $contentType]))->with('alert', [
            'type' => 'success',
            'message' => 'Champ ajouté au type de contenu.',
        ]);
    }

    public function edit(CmsContentType $contentType, CmsContentField $field)
    {
        return view('admin.cms.content_types.field_form')->with(compact('contentType', 'field'))->with('cmsWorkspace', $this->workspaceMeta());
    }

    public function update(Request $request, CmsContentType $contentType, CmsContentField $field)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'key' => 'nullable|string|max:191|unique:cms_content_fields,key,' . $field->id,
            'field_type' => 'required|in:text,textarea,richtext,image,number,boolean,date,datetime,url,json',
            'sort_order' => 'nullable|integer|min:0',
            'default_value' => 'nullable|string',
            'help_text' => 'nullable|string',
            'options' => 'nullable|string',
            'is_required' => 'nullable|boolean',
        ]);

        $data['is_required'] = $request->boolean('is_required');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        $field->update($data);

        return redirect()->to($this->workspaceRoute('admin.cms.content-types.edit', ['contentType' => $contentType]))->with('alert', [
            'type' => 'success',
            'message' => 'Champ mis à jour.',
        ]);
    }

    public function destroy(CmsContentType $contentType, CmsContentField $field)
    {
        $field->delete();

        return redirect()->to($this->workspaceRoute('admin.cms.content-types.edit', ['contentType' => $contentType]))->with('alert', [
            'type' => 'success',
            'message' => 'Champ supprimé.',
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
            'kende' => ['key' => 'kende', 'label' => 'Kende', 'eyebrow' => 'CMS Mobilite', 'description' => 'Champs structures pour les contenus transport.'],
            'mema' => ['key' => 'mema', 'label' => 'Mema', 'eyebrow' => 'CMS Colis', 'description' => 'Champs structures pour les contenus logistiques.'],
            default => ['key' => 'bantudelice', 'label' => 'BantuDelice', 'eyebrow' => 'CMS Food ops', 'description' => 'Champs structures pour les contenus food.'],
        };
    }

    private function workspaceRoute(string $route, array $parameters = []): string
    {
        return route($route, array_merge($parameters, ['workspace' => $this->workspace()]));
    }
}
