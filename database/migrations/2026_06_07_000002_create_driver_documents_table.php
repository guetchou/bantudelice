<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriverDocumentsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('driver_documents')) return;

        Schema::create('driver_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('driver_id')->index();
            $table->string('type', 40)->index(); // permis|assurance|cni
            $table->string('file_path', 500);
            $table->string('original_name', 255)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('driver_documents');
    }
}
