<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // Historical line items — restrict delete on catalog references.
            $table->foreignId('ticket_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('registration_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('description');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();

            $table->index('organization_id');
            $table->index('order_id');
            $table->index('ticket_type_id');
            $table->index('registration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
