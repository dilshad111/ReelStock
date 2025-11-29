<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ContentSecurityPolicy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!app()->environment('local')) {
            $devHosts = array_filter(array_map('trim', explode(',', env('CSP_DEV_HOSTS', 'http://localhost:5173'))));
            $scriptSources = array_merge(["'self'", "'unsafe-eval'", "'unsafe-inline'"], $devHosts, ['https://cdn.jsdelivr.net']);

            $csp = sprintf(
                "script-src %s; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; img-src 'self' data: https:; font-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com https://cdn.jsdelivr.net;",
                implode(' ', $scriptSources)
            );

            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
