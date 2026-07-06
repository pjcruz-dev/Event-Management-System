<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('refund_amount', 10, 2)->nullable()->after('paid_at');
            $table->timestamp('refunded_at')->nullable()->after('refund_amount');
            $table->string('refund_reason')->nullable()->after('refunded_at');
            $table->string('stripe_refund_id')->nullable()->after('refund_reason');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['refund_amount', 'refunded_at', 'refund_reason', 'stripe_refund_id']);
        });
    }
};
