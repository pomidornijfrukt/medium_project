<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CorsTestController extends Controller
{
    /**
     * Simple CORS test endpoint
     */
    public function test(Request $request)
    {
        return response()->json([
            'message' => 'CORS test successful',
            'origin' => $request->headers->get('Origin'),
            'method' => $request->getMethod(),
            'headers' => $request->headers->all(),
            'timestamp' => now()->toISOString(),
        ]);
    }
}
