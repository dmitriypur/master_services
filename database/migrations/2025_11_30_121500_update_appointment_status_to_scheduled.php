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

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE appointments ALTER COLUMN status SET DEFAULT 'scheduled'");
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE appointments MODIFY status VARCHAR(255) DEFAULT 'scheduled'");
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE appointments ALTER COLUMN status SET DEFAULT 'planned'");
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE appointments MODIFY status VARCHAR(255) DEFAULT 'planned'");
        }

        DB::table('appointments')
            ->where('status', 'scheduled')
            ->update(['status' => 'planned']);
    }
};
