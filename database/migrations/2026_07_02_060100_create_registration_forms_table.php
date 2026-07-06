<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_forms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            // fields: [{ key, type, label, required, options }]
            $table->json('fields');
            $table->timestamps();

            $table->unique('event_id');
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_forms');
    }
};
