<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsGuest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (!in_array(Auth::user()->role, ['guest', 'admin', 'manager'])) {
            abort(403, 'Anda tidak memiliki akses ke halaman monitoring ini.');
        }

        return $next($request);
    }
}
