<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveCommittee
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isPanitia(), 403, 'Hak panitia belum aktif, sudah berakhir, atau telah dicabut.');
        return $next($request);
    }
}
