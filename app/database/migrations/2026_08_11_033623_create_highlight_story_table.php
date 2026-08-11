<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('highlight_story', function (Blueprint $table) {
        $table->id();
        $table->foreignId('highlight_id')->constrained()->onDelete('cascade');
        $table->foreignId('story_id')->constrained()->onDelete('cascade');
        $table->unsignedInteger('order')->default(0);
        $table->timestamps();

        $table->unique(['highlight_id', 'story_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('highlight_story');
}
};
