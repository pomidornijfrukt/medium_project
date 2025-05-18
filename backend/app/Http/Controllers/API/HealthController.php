<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    /**
     * API health check endpoint
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function check()
    {
        return response()->json([
            'success' => true,
            'message' => 'API is running',
            'data' => [
                'version' => config('app.version', '1.0.0'),
                'environment' => config('app.env'),
                'timestamp' => now()->toIso8601String()
            ]
        ]);
    }
}
