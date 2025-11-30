<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NgrokSkipBrowserWarning
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('ngrok-skip-browser-warning', '1');

        return $response;
    }
}
