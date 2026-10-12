<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the request locale from (in order) the authenticated user's
 * preference, the session, the browser's Accept-Language header, then the
 * application default. Only the supported locales are applied (FR-037).
 */
class SetLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = ['en', 'ar'];

        $locale = $request->user()?->locale
            ?? $request->session()->get('locale')
            ?? $request->getPreferredLanguage($supported)
            ?? config('app.locale');

        if (in_array($locale, $supported, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
