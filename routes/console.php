<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:make-superadmin {email}', function (string $email) {
    $user = User::query()->where('email', $email)->first();
    if (! $user) {
        $this->error('User not found');

        return 1;
    }
    $user->role = 'superadmin';
    $user->save();
    $this->info('Role updated to superadmin');

    return 0;
})->purpose('Grant superadmin role to a user by email');
