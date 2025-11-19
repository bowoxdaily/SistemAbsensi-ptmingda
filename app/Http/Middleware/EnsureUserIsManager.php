<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsManager
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Only allow manager role
        if (Auth::user()->role !== 'manager') {
            // Return 403 Forbidden response with custom error page
            abort(403, 'Anda tidak memiliki akses ke halaman ini. Halaman ini khusus untuk Manager.');
        }

        return $next($request);
    }
}
