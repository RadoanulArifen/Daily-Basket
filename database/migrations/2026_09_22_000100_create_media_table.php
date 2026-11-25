<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 30);
            $table->string('directory');             // media/2026/09/<uuid>
            $table->string('original_name');
            $table->string('mime_type', 50);
            $table->unsignedInteger('size');         // bytes of the largest stored version
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->json('alt')->nullable();         // {"en": "...", "ar": "..."}
            $table->json('sizes');                   // {"thumb": "thumb.webp", "medium": "medium.webp", ...}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
