<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_path')->nullable()->after('password');
            $table->string('phone', 32)->nullable()->after('avatar_path');
            $table->string('timezone', 64)->default('UTC')->after('phone');
            $table->string('locale', 16)->default('en')->after('timezone');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropColumn(['avatar_path', 'phone', 'timezone', 'locale']);
        });
    }
};
