<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->string('registration_mode', 32)->default('open')->after('visibility');
            $table->json('rsvp_settings')->nullable()->after('registration_mode');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn(['registration_mode', 'rsvp_settings']);
        });
    }
};
