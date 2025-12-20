<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_services', function (Blueprint $table): void {
            if (! Schema::hasColumn('master_services', 'duration_minutes')) {
                $table->integer('duration_minutes')->default(60);
            }
        });
    }

    public function down(): void
    {
        Schema::table('master_services', function (Blueprint $table): void {
            if (Schema::hasColumn('master_services', 'duration_minutes')) {
                $table->dropColumn('duration_minutes');
            }
        });
    }
};
