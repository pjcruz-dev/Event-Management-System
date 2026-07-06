<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_session_speaker', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_session_id')->constrained('event_sessions')->cascadeOnDelete();
            $table->foreignId('speaker_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('speaker');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['event_session_id', 'speaker_id']);
            $table->index('speaker_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_session_speaker');
    }
};
