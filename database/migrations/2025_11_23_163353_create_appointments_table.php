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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id')->constrained('users');
            $table->bigInteger('client_id')->nullable();
            $table->foreignId('service_id')->nullable()->constrained('services');
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('status')->default('planned');
            $table->string('source')->default('manual');
            $table->timestampTz('reminder_for_master_sent_at')->nullable();
            $table->timestampTz('reminder_for_client_sent_at')->nullable();
            $table->timestamps();

            $table->index(['master_id', 'starts_at']);
            $table->index(['master_id', 'status', 'starts_at']);
            $table->index(['master_id', 'status', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
