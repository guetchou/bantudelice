<?php

namespace Tests\Feature;

use App\CmsContent;
use App\CmsContentField;
use App\CmsContentFieldValue;
use App\CmsContentRevision;
use App\CmsContentStatusLog;
use App\CmsContentType;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CmsContentCrudTest extends TestCase
{
    use RefreshDatabase;

    private function createContentTypeWithImageField(): array
    {
        $type = CmsContentType::create([
            'name' => 'Pages',
            'slug' => 'pages',
            'description' => 'Pages CMS',
            'is_active' => true,
            'supports_revisions' => true,
        ]);

        $imageField = CmsContentField::create([
            'content_type_id' => $type->id,
            'name' => 'Hero image',
            'key' => 'hero_image_test',
            'field_type' => 'image',
            'is_required' => false,
            'sort_order' => 1,
        ]);

        return [$type, $imageField];
    }

    private function createCmsContent(CmsContentType $type, CmsContentField $imageField, User $author): CmsContent
    {
        $content = CmsContent::create([
            'content_type_id' => $type->id,
            'title' => 'Page test',
            'slug' => 'page-test',
            'status' => 'published',
            'excerpt' => 'Contenu de test',
            'author_id' => $author->id,
            'editor_id' => $author->id,
            'published_at' => now(),
        ]);

        CmsContentFieldValue::create([
            'content_id' => $content->id,
            'content_field_id' => $imageField->id,
            'value' => 'images/cms/cms-test-delete.png',
        ]);

        CmsContentRevision::create([
            'content_id' => $content->id,
            'revision_number' => 1,
            'payload' => ['title' => 'Page test'],
            'created_by' => $author->id,
            'note' => 'Initial revision',
        ]);

        CmsContentStatusLog::create([
            'content_id' => $content->id,
            'from_status' => null,
            'to_status' => 'published',
            'changed_by' => $author->id,
            'note' => 'Creation',
        ]);

        return $content;
    }

    public function test_admin_can_delete_cms_content_and_cascaded_records(): void
    {
        [$type, $imageField] = $this->createContentTypeWithImageField();
        $admin = User::factory()->create(['type' => 'admin']);
        $this->grantAdminWorkspace($admin);
        $content = $this->createCmsContent($type, $imageField, $admin);

        File::ensureDirectoryExists(public_path('images/cms'));
        File::put(public_path('images/cms/cms-test-delete.png'), 'cms');

        $this->actingAs($admin)
            ->delete(route('admin.cms.contents.destroy', $content))
            ->assertRedirect(route('admin.cms.contents.index', ['workspace' => 'bantudelice']));

        $this->assertDatabaseMissing('cms_contents', ['id' => $content->id]);
        $this->assertDatabaseMissing('cms_content_field_values', ['content_id' => $content->id]);
        $this->assertDatabaseMissing('cms_content_revisions', ['content_id' => $content->id]);
        $this->assertDatabaseMissing('cms_content_status_logs', ['content_id' => $content->id]);
        $this->assertFalse(File::exists(public_path('images/cms/cms-test-delete.png')));
    }

    public function test_standard_user_is_redirected_and_cannot_delete_cms_content(): void
    {
        [$type, $imageField] = $this->createContentTypeWithImageField();
        $admin = User::factory()->create(['type' => 'admin']);
        $standardUser = User::factory()->create(['type' => 'user']);
        $content = $this->createCmsContent($type, $imageField, $admin);

        $this->actingAs($standardUser)
            ->delete(route('admin.cms.contents.destroy', $content))
            ->assertStatus(302);

        $this->assertDatabaseHas('cms_contents', ['id' => $content->id]);
    }
}
