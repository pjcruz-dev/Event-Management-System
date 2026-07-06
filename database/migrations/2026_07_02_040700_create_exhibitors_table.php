<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exhibitors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('website_url')->nullable();
            $table->string('contact_email')->nullable();
            $table->timestamps();

            $table->index('organization_id');
            $table->index(['organization_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exhibitors');
    }
};
