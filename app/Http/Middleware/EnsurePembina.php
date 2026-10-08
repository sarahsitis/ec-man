<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePembina
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->role === 'pembina') {
            return $next($request);
        }
        
        abort(403, 'Akses Ditolak. Halaman ini hanya untuk Pembina Ekstrakurikuler.');
    }
}
