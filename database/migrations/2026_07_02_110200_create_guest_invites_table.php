<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_invites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('invitation_token', 128)->unique();
            $table->string('status', 32)->default('pending');
            $table->string('rsvp_response', 32)->nullable();
            $table->string('household_name')->nullable();
            $table->string('group_label')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedTinyInteger('plus_one_limit')->nullable();
            $table->foreignId('table_id')->nullable()->constrained('event_tables')->nullOnDelete();
            $table->unsignedBigInteger('registration_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id', 'email']);
            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_invites');
    }
};
