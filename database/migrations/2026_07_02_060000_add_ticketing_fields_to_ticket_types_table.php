<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_types', function (Blueprint $table): void {
            $table->string('type_tag')->nullable()->after('name');
            $table->unsignedSmallInteger('per_order_limit')->default(10)->after('quantity_sold');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_types', function (Blueprint $table): void {
            $table->dropColumn(['type_tag', 'per_order_limit']);
        });
    }
};
