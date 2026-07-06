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
            $table->string('qr_token_hash', 64)->nullable()->unique()->after('custom_fields');
            $table->text('qr_token_encrypted')->nullable()->after('qr_token_hash');
            $table->foreignId('checked_in_by')->nullable()->after('checked_in_at')->constrained('users')->nullOnDelete();
            $table->string('check_in_device_id')->nullable()->after('checked_in_by');
            $table->string('check_in_gate')->nullable()->after('check_in_device_id');
            $table->decimal('check_in_latitude', 10, 7)->nullable()->after('check_in_gate');
            $table->decimal('check_in_longitude', 10, 7)->nullable()->after('check_in_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->dropForeign(['checked_in_by']);
            $table->dropColumn([
                'qr_token_hash',
                'qr_token_encrypted',
                'checked_in_by',
                'check_in_device_id',
                'check_in_gate',
                'check_in_latitude',
                'check_in_longitude',
            ]);
        });
    }
};
