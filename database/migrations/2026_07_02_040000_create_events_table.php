<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            // Owned by organization — cascade when org is removed.
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('venue')->nullable();
            $table->string('timezone')->default('UTC');
            $table->unsignedInteger('capacity')->nullable();
            $table->string('status')->default('draft');
            $table->string('visibility')->default('private');
            // theme_config: { primary_color, secondary_color, font, logo_url, hero_image_url, layout_variant }
            $table->json('theme_config')->nullable();
            // landing_page_config: { blocks: [{ type, settings }] }
            $table->json('landing_page_config')->nullable();
            $table->string('custom_domain')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'slug']);
            $table->index('organization_id');
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
