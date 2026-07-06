<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Named event_sessions — Laravel reserves `sessions` for HTTP session storage.
        Schema::create('event_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            // Track is optional; null on delete preserves session history if track removed.
            $table->foreignId('track_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('room')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('organization_id');
            $table->index(['organization_id', 'event_id']);
            $table->index(['organization_id', 'starts_at']);
            $table->index('track_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_sessions');
    }
};
