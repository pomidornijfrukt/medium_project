<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        if ($request->user()->Role !== $role) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You need ' . $role . ' role to access this resource.'
            ], 403);
        }

        return $next($request);
    }
}
