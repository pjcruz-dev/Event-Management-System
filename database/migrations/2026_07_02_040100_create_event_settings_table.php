<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_settings', function (Blueprint $table): void {
            $table->id();
            // Denormalized for tenant indexing — cascade with organization.
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            // Owned by event — cascade when event is removed.
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            // value: arbitrary JSON payload validated per key in later phases.
            $table->json('value');
            $table->timestamps();

            $table->unique(['event_id', 'key']);
            $table->index('organization_id');
            $table->index(['organization_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_settings');
    }
};
