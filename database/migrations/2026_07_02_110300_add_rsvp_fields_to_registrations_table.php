<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->string('rsvp_response', 32)->nullable()->after('status');
            $table->foreignId('guest_invite_id')->nullable()->after('rsvp_response')
                ->constrained('guest_invites')->nullOnDelete();
            $table->boolean('is_plus_one')->default(false)->after('guest_invite_id');
            $table->foreignId('primary_registration_id')->nullable()->after('is_plus_one')
                ->constrained('registrations')->nullOnDelete();
            $table->foreignId('table_id')->nullable()->after('primary_registration_id')
                ->constrained('event_tables')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('table_id');
            $table->dropConstrainedForeignId('primary_registration_id');
            $table->dropColumn(['is_plus_one']);
            $table->dropConstrainedForeignId('guest_invite_id');
            $table->dropColumn(['rsvp_response']);
        });
    }
};
