<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->string('certificate_number')->unique();
            $table->timestamp('issued_at');
            // template_config: { title, subtitle, signature_name, background_url }
            $table->json('template_config')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->unique('registration_id');
            $table->index('organization_id');
            $table->index(['organization_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
