<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            // User is optional for guest checkout — null on delete preserves registration record.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Ticket type is a catalog reference — restrict delete while registrations exist.
            $table->foreignId('ticket_type_id')->constrained()->restrictOnDelete();
            $table->string('registration_number')->unique();
            $table->string('status')->default('pending');
            $table->string('attendee_first_name');
            $table->string('attendee_last_name');
            $table->string('attendee_email');
            $table->string('attendee_phone')->nullable();
            // custom_fields: { dietary_requirements, company, job_title, ... }
            $table->json('custom_fields')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_id');
            $table->index(['organization_id', 'event_id']);
            $table->index(['organization_id', 'status']);
            $table->index('user_id');
            $table->index('ticket_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
