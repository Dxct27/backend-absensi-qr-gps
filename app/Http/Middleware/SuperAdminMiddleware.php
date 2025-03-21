<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()->group !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return $next($request);
    }
}
