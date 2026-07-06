<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('discount_type');
            $table->decimal('discount_value', 10, 2);
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('times_used')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['event_id', 'code']);
            $table->index('organization_id');
            $table->index(['organization_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
