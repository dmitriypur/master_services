<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('subscription_status')->default('trial');
            $table->timestampTz('trial_ends_at')->nullable();
        });

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE users
                ADD CONSTRAINT users_subscription_status_check
                CHECK (subscription_status IN ('trial', 'active', 'inactive'))
            SQL);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('subscription_status');
            $table->dropColumn('trial_ends_at');
        });
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE users
                DROP CONSTRAINT IF EXISTS users_subscription_status_check
            SQL);
        }
    }
};
