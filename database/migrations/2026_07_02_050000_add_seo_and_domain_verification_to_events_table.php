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
            $table->string('meta_title')->nullable()->after('custom_domain');
            $table->string('meta_description', 500)->nullable()->after('meta_title');
            $table->string('og_image_path')->nullable()->after('meta_description');
            // DNS verification flow ships in Phase 15 — schema hook only for now.
            $table->string('custom_domain_verification_status')->default('unverified')->after('og_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn([
                'meta_title',
                'meta_description',
                'og_image_path',
                'custom_domain_verification_status',
            ]);
        });
    }
};
