<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if user status is inactive or banned
            if (in_array($user->Status, ['inactive', 'banned'])) {
                // Revoke all tokens for this user
                $user->tokens()->delete();
                
                // Clear the auth session
                Auth::logout();
                
                return response()->json([
                    'message' => 'Your account has been ' . $user->Status . '. Please contact support.',
                    'status' => $user->Status,
                    'error' => 'ACCOUNT_' . strtoupper($user->Status)
                ], 401);
            }
        }

        return $next($request);
    }
}
