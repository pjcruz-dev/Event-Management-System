<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_invites', function (Blueprint $table): void {
            $table->timestamp('last_reminder_sent_at')->nullable()->after('responded_at');
            $table->unsignedTinyInteger('reminder_count')->default(0)->after('last_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('guest_invites', function (Blueprint $table): void {
            $table->dropColumn(['last_reminder_sent_at', 'reminder_count']);
        });
    }
};
