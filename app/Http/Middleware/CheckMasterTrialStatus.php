<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckMasterTrialStatus
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || $user->role !== 'master' || $user->isTrialExpired()) {
            return response()->json([
                'message' => 'Доступ запрещён: требуется активная подписка или валидный триал.',
            ], 403);
        }

        return $next($request);
    }
}
