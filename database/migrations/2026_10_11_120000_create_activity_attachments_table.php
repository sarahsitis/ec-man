<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('original_name');
            $table->string('path')->unique();
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size');
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('uploader_name');
            $table->timestamps();
            $table->index(['activity_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_attachments');
    }
};
