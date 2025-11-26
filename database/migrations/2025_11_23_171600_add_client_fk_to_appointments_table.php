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
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->index('client_id', 'appointments_client_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign(['client_id']);
            $table->dropIndex('appointments_client_id_idx');
        });
    }
};
