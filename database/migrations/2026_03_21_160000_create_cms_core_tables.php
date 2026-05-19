<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_content_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('supports_revisions')->default(true);
            $table->timestamps();
        });

        Schema::create('cms_content_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_type_id')->constrained('cms_content_types')->cascadeOnDelete();
            $table->string('name');
            $table->string('key')->unique();
            $table->string('field_type', 50);
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('default_value')->nullable();
            $table->text('help_text')->nullable();
            $table->longText('options')->nullable();
            $table->timestamps();
        });

        Schema::create('cms_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_type_id')->constrained('cms_content_types')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('status', 50)->default('draft');
            $table->text('excerpt')->nullable();
            $table->string('layout')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->unsignedBigInteger('editor_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['content_type_id', 'slug']);
            $table->index(['content_type_id', 'status']);
        });

        Schema::create('cms_content_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('cms_contents')->cascadeOnDelete();
            $table->foreignId('content_field_id')->constrained('cms_content_fields')->cascadeOnDelete();
            $table->longText('value')->nullable();
            $table->timestamps();

            $table->unique(['content_id', 'content_field_id']);
        });

        Schema::create('cms_content_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('cms_contents')->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->longText('payload');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['content_id', 'revision_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_content_revisions');
        Schema::dropIfExists('cms_content_field_values');
        Schema::dropIfExists('cms_contents');
        Schema::dropIfExists('cms_content_fields');
        Schema::dropIfExists('cms_content_types');
    }
};
