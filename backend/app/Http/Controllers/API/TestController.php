<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function test()
    {
        return response()->json([
            'success' => true,
            'message' => 'API is working!',
            'time' => now()->toDateTimeString()
        ]);
    }
}
