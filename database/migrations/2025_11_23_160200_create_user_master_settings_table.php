<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_master_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('address')->nullable();
            $table->json('work_days')->nullable();
            $table->time('work_time_from')->nullable();
            $table->time('work_time_to')->nullable();
            $table->integer('slot_duration_min')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lon', 10, 7)->nullable();
            $table->timestamps();
            $table->unique('user_id');
        });

        if (Schema::hasColumn('users', 'address')
            || Schema::hasColumn('users', 'work_days')
            || Schema::hasColumn('users', 'work_time_from')
            || Schema::hasColumn('users', 'work_time_to')
            || Schema::hasColumn('users', 'slot_duration_min')
            || Schema::hasColumn('users', 'lat')
            || Schema::hasColumn('users', 'lon')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'address')) {
                    $table->dropColumn('address');
                }
                if (Schema::hasColumn('users', 'work_days')) {
                    $table->dropColumn('work_days');
                }
                if (Schema::hasColumn('users', 'work_time_from')) {
                    $table->dropColumn('work_time_from');
                }
                if (Schema::hasColumn('users', 'work_time_to')) {
                    $table->dropColumn('work_time_to');
                }
                if (Schema::hasColumn('users', 'slot_duration_min')) {
                    $table->dropColumn('slot_duration_min');
                }
                if (Schema::hasColumn('users', 'lat')) {
                    $table->dropColumn('lat');
                }
                if (Schema::hasColumn('users', 'lon')) {
                    $table->dropColumn('lon');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_master_settings');

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable();
            }
            if (! Schema::hasColumn('users', 'work_days')) {
                $table->json('work_days')->nullable();
            }
            if (! Schema::hasColumn('users', 'work_time_from')) {
                $table->time('work_time_from')->nullable();
            }
            if (! Schema::hasColumn('users', 'work_time_to')) {
                $table->time('work_time_to')->nullable();
            }
            if (! Schema::hasColumn('users', 'slot_duration_min')) {
                $table->integer('slot_duration_min')->nullable();
            }
            if (! Schema::hasColumn('users', 'lat')) {
                $table->decimal('lat', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('users', 'lon')) {
                $table->decimal('lon', 10, 7)->nullable();
            }
        });
    }
};
