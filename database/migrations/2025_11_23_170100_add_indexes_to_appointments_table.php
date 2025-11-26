<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->index(['master_id', 'starts_at'], 'appointments_master_starts_idx');
            $table->index(['master_id', 'status', 'starts_at'], 'appointments_master_status_starts_idx');
            $table->index(['master_id', 'status', 'ends_at'], 'appointments_master_status_ends_idx');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex('appointments_master_starts_idx');
            $table->dropIndex('appointments_master_status_starts_idx');
            $table->dropIndex('appointments_master_status_ends_idx');
        });
    }
};
