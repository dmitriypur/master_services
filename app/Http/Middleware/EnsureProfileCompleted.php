<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Если пользователь авторизован, является мастером, но профиль не активен (не завершен онбординг)
        if ($user && $user->role === 'master' && ! $user->is_active) {
            // Если запрос хочет JSON (API), отдаем ошибку с редиректом
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Профиль не завершен',
                    'redirect' => url('/master/register'),
                ], 403);
            }

            // Иначе редиректим на страницу настроек (где можно завершить профиль)
            return redirect()->route('master.settings.edit');
        }

        return $next($request);
    }
}
