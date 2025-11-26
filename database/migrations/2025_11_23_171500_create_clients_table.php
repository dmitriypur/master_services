<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->bigInteger('telegram_id')->nullable();
            $table->string('whatsapp_phone')->nullable();
            $table->json('preferred_channels')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'name'], 'clients_user_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
