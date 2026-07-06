<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // One order per registration — cascade when registration removed.
            $table->foreignId('registration_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->string('status')->default('pending');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('tax_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->char('currency', 3)->default('USD');
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_id');
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'event_id']);
            $table->index('registration_id');
            $table->index('coupon_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
