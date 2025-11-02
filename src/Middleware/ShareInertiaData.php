<?php

namespace Enes\TranslatedRoutes\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Enes\TranslatedRoutes\Facades\TranslatedRoutes;

class ShareInertiaData
{
    public function handle(Request $request, Closure $next): Response
    {
        if (class_exists(\Inertia\Inertia::class)) {
            \Inertia\Inertia::share([
                'locale' => fn () => TranslatedRoutes::getLocaleData(),
            ]);
        }

        return $next($request);
    }
}
