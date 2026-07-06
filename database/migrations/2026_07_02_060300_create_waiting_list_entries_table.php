<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waiting_list_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('attendee_first_name');
            $table->string('attendee_last_name');
            $table->string('attendee_email');
            $table->string('attendee_phone')->nullable();
            $table->json('custom_fields')->nullable();
            $table->string('status')->default('waiting');
            $table->timestamps();

            $table->index('organization_id');
            $table->index(['event_id', 'ticket_type_id']);
            $table->index('attendee_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waiting_list_entries');
    }
};
