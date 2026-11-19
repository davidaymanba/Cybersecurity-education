<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public const SUPPORTED = ['en', 'ar'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->session()->get('locale', config('app.locale'));

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.fallback_locale', 'en');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
