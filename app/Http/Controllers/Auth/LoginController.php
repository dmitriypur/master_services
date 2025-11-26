<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);
        $remember = (bool) $request->input('remember', false);

        if (! Auth::attempt($credentials, $remember)) {
            return back()->withErrors(['email' => 'Неверные учетные данные'])->withInput();
        }

        $request->session()->regenerate();

        return redirect()->intended('/master/calendar');
    }
}
