<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_sessions', function (Blueprint $table): void {
            $table->boolean('is_published')->default(false)->after('sort_order');
        });

        Schema::table('exhibitors', function (Blueprint $table): void {
            $table->json('materials')->nullable()->after('contact_email');
        });

        Schema::create('session_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_session_id')->constrained('event_sessions')->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_session_id', 'registration_id']);
            $table->index('organization_id');
            $table->index(['organization_id', 'event_id']);
            $table->index('registration_id');
        });

        Schema::create('exhibitor_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exhibitor_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();

            $table->unique(['exhibitor_id', 'email']);
            $table->index('organization_id');
        });

        Schema::create('exhibitor_leads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exhibitor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scanned_by')->constrained('exhibitor_contacts')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->unique(['exhibitor_id', 'registration_id']);
            $table->index('organization_id');
            $table->index(['organization_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exhibitor_leads');
        Schema::dropIfExists('exhibitor_contacts');
        Schema::dropIfExists('session_registrations');

        Schema::table('exhibitors', function (Blueprint $table): void {
            $table->dropColumn('materials');
        });

        Schema::table('event_sessions', function (Blueprint $table): void {
            $table->dropColumn('is_published');
        });
    }
};
