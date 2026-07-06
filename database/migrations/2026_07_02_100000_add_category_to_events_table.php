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
            $table->string('category', 50)->nullable()->after('visibility');
            $table->index(['status', 'visibility', 'category']);
            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropIndex(['status', 'visibility', 'category']);
            $table->dropIndex(['starts_at']);
            $table->dropColumn('category');
        });
    }
};
