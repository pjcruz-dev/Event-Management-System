<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booths', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            // Booth is owned by exhibitor — cascade when exhibitor removed.
            $table->foreignId('exhibitor_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('location')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'code']);
            $table->index('organization_id');
            $table->index('exhibitor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booths');
    }
};
