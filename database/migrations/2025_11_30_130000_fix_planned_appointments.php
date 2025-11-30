<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('appointments')
            ->where('status', 'planned')
            ->update(['status' => 'scheduled']);
    }

    public function down(): void
    {
        // No down action as we want to keep them scheduled
    }
};
