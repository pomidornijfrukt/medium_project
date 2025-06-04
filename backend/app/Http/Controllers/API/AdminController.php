<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Post;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Check if the authenticated user has admin role
     */
    private function checkAdminRole(Request $request)
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

        if ($request->user()->Role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You need admin role to access this resource.'
            ], 403);
        }

        return null; // No error, user is admin
    }

    /**
     * Get all users for admin panel
     */
    public function getUsers(Request $request)
    {
        $roleCheck = $this->checkAdminRole($request);
        if ($roleCheck) return $roleCheck;

        try {
            // Use raw column names as they appear in database
            $users = User::select('UID', 'Username', 'Email', 'Role', 'Status', 'created_at as CreatedAt', 'LastLoginAt')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'users' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all posts for admin panel
     */
    public function getPosts(Request $request)
    {
        $roleCheck = $this->checkAdminRole($request);
        if ($roleCheck) return $roleCheck;

        try {
            $posts = Post::select('PostID', 'Topic as Title', 'Content', 'Author as AuthorID', 'PostType', 'ParentPostID', 'Status', 'created_at', 'updated_at')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'posts' => $posts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user details with their posts
     */
    public function getUserDetails(Request $request, $userId)
    {
        $roleCheck = $this->checkAdminRole($request);
        if ($roleCheck) return $roleCheck;

        try {
            $user = User::where('UID', $userId)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $posts = Post::where('Author', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'user' => $user,
                'posts' => $posts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user role
     */
    public function updateUserRole(Request $request, $userId)
    {
        $roleCheck = $this->checkAdminRole($request);
        if ($roleCheck) return $roleCheck;

        try {
            $validator = Validator::make($request->all(), [
                'role' => 'required|string|in:admin,moderator,member'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('UID', $userId)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $user->Role = $request->role;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'User role updated successfully',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user status
     */
    public function updateUserStatus(Request $request, $userId)
    {
        $roleCheck = $this->checkAdminRole($request);
        if ($roleCheck) return $roleCheck;

        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:active,inactive,banned'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('UID', $userId)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $user->Status = $request->status;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a post (admin)
     */
    public function deletePost(Request $request, $postId)
    {
        $roleCheck = $this->checkAdminRole($request);
        if ($roleCheck) return $roleCheck;

        try {
            $post = Post::where('PostID', $postId)->first();
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found'
                ], 404);
            }

            // Delete related data first (tags relationship)
            $post->tags()->detach();
            
            // Delete the post
            $post->delete();

            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(Request $request)
    {
        $roleCheck = $this->checkAdminRole($request);
        if ($roleCheck) return $roleCheck;

        try {
            $stats = [
                'total_users' => User::count(),
                'total_posts' => Post::count(),
                'active_users' => User::where('Status', 'active')->count(),
                'recent_posts' => Post::where('created_at', '>=', now()->subDays(7))->count(),
                'admin_users' => User::where('Role', 'admin')->count(),
                'moderator_users' => User::where('Role', 'moderator')->count(),
                'member_users' => User::where('Role', 'member')->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available roles
     */
    public function getRoles(Request $request)
    {
        $roleCheck = $this->checkAdminRole($request);
        if ($roleCheck) return $roleCheck;

        try {
            $roles = Role::select('RoleName', 'RoleDescription')->get();

            return response()->json([
                'success' => true,
                'roles' => $roles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
