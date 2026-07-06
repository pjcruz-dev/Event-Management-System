<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            // null = applies to whole order; otherwise list of ticket_type ids
            $table->json('applicable_ticket_type_ids')->nullable()->after('discount_value');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            $table->dropColumn('applicable_ticket_type_ids');
        });
    }
};
