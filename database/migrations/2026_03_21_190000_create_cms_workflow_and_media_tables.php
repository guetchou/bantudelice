<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cms_content_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('content_id');
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->foreign('content_id')->references('id')->on('cms_contents')->onDelete('cascade');
        });

        Schema::create('cms_media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_media_assets');
        Schema::dropIfExists('cms_content_status_logs');
    }
};
