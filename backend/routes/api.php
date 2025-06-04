<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\TagController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\HealthController;
use App\Http\Controllers\API\CorsTestController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes
Route::get('/health', [HealthController::class, 'check']);
Route::get('/test', [App\Http\Controllers\API\TestController::class, 'test']);
Route::get('/cors-test', [CorsTestController::class, 'test']);
Route::post('/cors-test', [CorsTestController::class, 'test']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Posts - Public
Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/tag/{tag}', [PostController::class, 'getByTag']);
Route::get('/posts/user/{user}', [PostController::class, 'getUserPosts']);
Route::get('/posts/{post}', [PostController::class, 'show']);
Route::get('/posts/{post}/linked', [PostController::class, 'getLinkedPosts']);

// Tags - Public
Route::get('/tags', [TagController::class, 'index']);
Route::get('/tags/{tag}', [TagController::class, 'show']);

// Users - Public
Route::get('/users/{user}/profile', [UserController::class, 'getPublicProfile']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/user/profile', [UserController::class, 'getProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/user/password', [UserController::class, 'updatePassword']);
    Route::delete('/user/account', [UserController::class, 'deleteAccount']);

    // Posts - Auth required
    Route::get('/user/posts', [PostController::class, 'getMyPosts']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::put('/posts/{post}', [PostController::class, 'update']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
    
    // Tags - Auth required
    Route::post('/tags', [TagController::class, 'store']);
    Route::put('/tags/{tag}', [TagController::class, 'update']);
    Route::delete('/tags/{tag}', [TagController::class, 'destroy']);
    
    // Admin routes - organized under /api/admin/ prefix
    Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {
        // User management
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::get('/users/{userId}', [AdminController::class, 'getUserDetails']);
        Route::put('/users/{userId}/role', [AdminController::class, 'updateUserRole']);
        Route::put('/users/{userId}/status', [AdminController::class, 'updateUserStatus']);
        
        // Post management
        Route::get('/posts', [AdminController::class, 'getPosts']);
        Route::delete('/posts/{postId}', [AdminController::class, 'deletePost']);
        
        // Dashboard & utilities
        Route::get('/stats', [AdminController::class, 'getDashboardStats']);
        Route::get('/roles', [AdminController::class, 'getRoles']);
    });
});
