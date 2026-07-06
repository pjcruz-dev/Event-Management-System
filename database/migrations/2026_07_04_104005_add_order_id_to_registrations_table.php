<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->foreignId('order_id')
                ->nullable()
                ->after('ticket_type_id')
                ->constrained()
                ->nullOnDelete();
        });

        DB::statement('UPDATE registrations SET order_id = (SELECT id FROM orders WHERE orders.registration_id = registrations.id LIMIT 1)');
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('order_id');
        });
    }
};
