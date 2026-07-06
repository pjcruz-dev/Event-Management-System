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
            $table->string('stripe_checkout_session_id')->nullable()->after('coupon_id');
            $table->string('stripe_payment_intent_id')->nullable()->after('stripe_checkout_session_id');
            $table->string('payment_method')->nullable()->after('stripe_payment_intent_id');

            $table->index('stripe_checkout_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['stripe_checkout_session_id']);
            $table->dropColumn([
                'stripe_checkout_session_id',
                'stripe_payment_intent_id',
                'payment_method',
            ]);
        });
    }
};
