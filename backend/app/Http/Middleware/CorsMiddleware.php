<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Debug logging
        \Log::info('CORS Middleware triggered', [
            'method' => $request->getMethod(),
            'origin' => $request->headers->get('Origin'),
            'url' => $request->url()
        ]);
        
        // Get the origin from the request
        $origin = $request->headers->get('Origin');
        
        // FOR TESTING: Allow all origins (REMOVE IN PRODUCTION!)
        $allowAllOrigins = true; // Set to false in production
        
        // Define allowed origins (used when $allowAllOrigins is false)
        $allowedOrigins = [
            '*',
            'http://localhost:5500',
            'http://127.0.0.1:5500',
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            'http://localhost:8080',
            'http://127.0.0.1:8080',
            'http://localhost:8888',
            'http://127.0.0.1:8888',
            'http://localhost:8000',
            'http://127.0.0.1:8000',
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'http://localhost:4173',
            'http://127.0.0.1:4173',
            'http://localhost:3001',
            'http://127.0.0.1:3001',
            'http://localhost:6969',
            'https://probable-space-pancake-7wvqjqvr9x6hxq4w-8888.app.github.dev',
            'http://probable-space-pancake-7wvqjqvr9x6hxq4w-8888.app.github.dev',
            // Add the codespace URL if needed
            'http://probable-space-pancake-7wvqjqvr9x6hxq4w-6969.app.github.dev',
            'https://probable-space-pancake-7wvqjqvr9x6hxq4w-6969.app.github.dev',
        ];
        
        // Handle preflight OPTIONS requests
        if ($request->getMethod() === "OPTIONS") {
            \Log::info('Handling OPTIONS request in CORS middleware');
            
            $response = response('', 200);
            
            // Set CORS headers for preflight
            if ($allowAllOrigins) {
                $response->headers->set('Access-Control-Allow-Origin', $origin ?: '*');
            } elseif ($origin && in_array($origin, $allowedOrigins)) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
            }
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept, Origin, X-Auth-Token');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '86400');
            
            \Log::info('Returning OPTIONS response with headers', $response->headers->all());
            return $response;
        }
        
        // Process the actual request
        $response = $next($request);
        
        // Set CORS headers for actual response
        if ($allowAllOrigins) {
            $response->headers->set('Access-Control-Allow-Origin', $origin ?: '*');
        } elseif ($origin && in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept, Origin, X-Auth-Token');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        
        \Log::info('Returning regular response with CORS headers');
        return $response;
    }
}
