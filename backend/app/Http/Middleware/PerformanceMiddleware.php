<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Add performance headers
        $response->headers->set('X-Response-Time', round($executionTime, 2) . 'ms');
        $response->headers->set('X-Request-ID', uniqid());
        
        // Add optimized CORS headers for better performance
        if ($request->getMethod() === 'OPTIONS') {
            $response->headers->set('Access-Control-Max-Age', '86400'); // Cache preflight for 24 hours
        }
        
        // Add security and performance headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Log slow requests
        if ($executionTime > 1000) { // Log requests taking more than 1 second
            \Log::warning("Slow request detected: {$request->method()} {$request->getPathInfo()} took {$executionTime}ms");
        }
        
        return $response;
    }
}
