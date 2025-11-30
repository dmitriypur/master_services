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
        if (! Schema::hasTable('master_schedule_exceptions')) {
            Schema::create('master_schedule_exceptions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('master_id')->constrained('users');
                $table->date('date');
                $table->string('type');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->string('description')->nullable();
                $table->timestamps();

                $table->index(['master_id', 'date']);
            });
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE master_schedule_exceptions
                ADD CONSTRAINT master_schedule_exceptions_type_check
                CHECK (type IN ('override', 'break', 'day_off'))
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('master_schedule_exceptions');
    }
};
