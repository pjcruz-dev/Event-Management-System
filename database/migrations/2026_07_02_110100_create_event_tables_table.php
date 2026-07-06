<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_tables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('capacity')->default(8);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('shape', 32)->default('round');
            $table->decimal('x', 8, 2)->nullable();
            $table->decimal('y', 8, 2)->nullable();
            $table->decimal('rotation', 8, 2)->nullable();
            $table->timestamps();

            $table->index(['event_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_tables');
    }
};
