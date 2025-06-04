<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Action;
use App\Models\UserNameChange;
use App\Models\EmailChange;
use App\Models\PasswordChange;
use App\Models\RoleChange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/users",
     *     summary="Display a listing of users (admin only)",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by username or email",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Filter by role",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "banned", "pending"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/PaginatedResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     * Display a listing of users (admin only).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = User::with('role');
        
        // Search by username or email
        if ($request->has('search')) {
            $query->where('Username', 'like', '%' . $request->search . '%')
                  ->orWhere('Email', 'like', '%' . $request->search . '%');
        }
        
        // Filter by role
        if ($request->has('role')) {
            $query->where('Role', $request->role);
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('Status', $request->status);
        }
        
        $users = $query->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * @OA\Get(
     *     path="/users/{uid}",
     *     summary="Display a specific user (admin only)",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     * Display a specific user (admin only).
     *
     * @param string $uid
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($uid)
    {
        $user = User::with(['role', 'posts' => function($query) {
            $query->where('Status', 'published');
        }])->find($uid);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * @OA\Put(
     *     path="/user/profile",
     *     summary="Update the authenticated user's profile",
     *     tags={"User Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="username", type="string", example="newusername"),
     *             @OA\Property(property="email", type="string", format="email", example="newemail@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     * Update the authenticated user's profile.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'username' => 'sometimes|string|max:255|unique:users,Username,' . $user->UID . ',UID',
            'email' => 'sometimes|string|email|max:255|unique:users,Email,' . $user->UID . ',UID',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Track changes
        if ($request->has('username') && $request->username !== $user->Username) {
            $usernameChange = UserNameChange::create([
                'Old UserName' => $user->Username,
                'New UserName' => $request->username
            ]);
            
            Action::create([
                'Author' => $user->UID,
                'Victim' => $user->UID,
                'ActionDateTime' => now(),
                'UserNameChangeID' => $usernameChange->UserNameChangeID
            ]);
            
            $user->Username = $request->username;
        }
        
        if ($request->has('email') && $request->email !== $user->Email) {
            $emailChange = EmailChange::create([
                'Old Email' => $user->Email,
                'New Email' => $request->email
            ]);
            
            Action::create([
                'Author' => $user->UID,
                'Victim' => $user->UID,
                'ActionDateTime' => now(),
                'EmailChangeID' => $emailChange->EmailChangeID
            ]);
            
            $user->Email = $request->email;
        }
        
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }

    /**
     * @OA\Put(
     *     path="/user/password",
     *     summary="Update the authenticated user's password",
     *     tags={"User Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password","password","password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password", example="currentpassword123"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Current password is incorrect",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     * Update the authenticated user's password.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        if (!Hash::check($request->current_password, $user->Password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 401);
        }
        
        // Track password change
        $passwordChange = PasswordChange::create([
            'Old Password Hash' => $user->Password,
            'New Password Hash' => Hash::make($request->password)
        ]);
        
        Action::create([
            'Author' => $user->UID,
            'Victim' => $user->UID,
            'ActionDateTime' => now(),
            'PassChangeID' => $passwordChange->PassChangeID
        ]);
        
        $user->Password = $request->password; // Will be hashed via model mutator
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully'
        ]);
    }

    /**
     * @OA\Put(
     *     path="/users/{uid}/role",
     *     summary="Update a user's role (admin only)",
     *     tags={"Admin - User Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"role"},
     *             @OA\Property(property="role", type="string", example="moderator")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User role updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User role updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or user already has this role",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     * Update a user's role (admin only).
     *
     * @param \Illuminate\Http\Request $request
     * @param string $uid
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateRole(Request $request, $uid)
    {
        $user = User::find($uid);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|exists:roles,Role Name',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        if ($user->Role === $request->role) {
            return response()->json([
                'success' => false,
                'message' => 'User already has this role'
            ], 422);
        }
        
        // Track role change
        $roleChange = RoleChange::create([
            'Old Role ID' => $user->Role,
            'New Role ID' => $request->role
        ]);
        
        Action::create([
            'Author' => $request->user()->UID,
            'Victim' => $user->UID,
            'ActionDateTime' => now(),
            'RoleChangeID' => $roleChange->RoleChangeID
        ]);
        
        $user->Role = $request->role;
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'User role updated successfully',
            'data' => $user
        ]);
    }

    /**
     * @OA\Put(
     *     path="/users/{uid}/status",
     *     summary="Update a user's status (admin only)",
     *     tags={"Admin - User Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"active", "banned", "pending"}, example="active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User status updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or user already has this status",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     * Update a user's status (admin only).
     *
     * @param \Illuminate\Http\Request $request
     * @param string $uid
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $uid)
    {
        $user = User::find($uid);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:active,banned,pending',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        if ($user->Status === $request->status) {
            return response()->json([
                'success' => false,
                'message' => 'User already has this status'
            ], 422);
        }
        
        $user->Status = $request->status;
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => $user
        ]);
    }

    /**
     * @OA\Get(
     *     path="/user/profile",
     *     summary="Get the authenticated user's profile with posts",
     *     tags={"User Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", allOf={
     *                 @OA\Schema(ref="#/components/schemas/User"),
     *                 @OA\Schema(
     *                     @OA\Property(property="posts", type="array", @OA\Items(ref="#/components/schemas/Post"))
     *                 )
     *             })
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     * Get the authenticated user's profile with posts.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfile(Request $request)
    {
        $user = $request->user()->load([
            'posts' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(5);
            }
        ]);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/user/account",
     *     summary="Delete the authenticated user's account",
     *     tags={"User Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password"},
     *             @OA\Property(property="password", type="string", format="password", example="userpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account deleted successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Password is incorrect",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     * Delete the authenticated user's account.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->password, $user->Password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is incorrect'
            ], 401);
        }

        // Soft delete user's posts
        $user->posts()->update([
            'Status' => 'deleted',
            'LastEditedAt' => now(),
        ]);

        // Update user status to deleted instead of hard delete
        $user->update([
            'Status' => 'deleted',
            'Email' => 'deleted_' . time() . '@deleted.com', // Ensure email uniqueness
            'Username' => 'deleted_user_' . time()
        ]);

        // Revoke all tokens
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/users/{uid}/profile",
     *     summary="Get public profile of a user",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Public user profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="UID", type="string", format="uuid"),
     *                 @OA\Property(property="Username", type="string", example="johndoe"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="posts", type="array", @OA\Items(ref="#/components/schemas/Post")),
     *                 @OA\Property(property="posts_count", type="integer", example=25)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     * Get public profile of a user.
     *
     * @param string $uid
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPublicProfile($uid)
    {
        $user = User::with([
            'posts' => function($query) {
                $query->where('Status', 'published')
                      ->orderBy('created_at', 'desc')
                      ->limit(10);
            }
        ])->find($uid);
        
        if (!$user || $user->Status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        // Return only public information
        $publicUser = $user->only(['UID', 'Username', 'created_at']);
        $publicUser['posts'] = $user->posts;
        $publicUser['posts_count'] = $user->posts()->where('Status', 'published')->count();
        
        return response()->json([
            'success' => true,
            'data' => $publicUser
        ]);
    }
}
