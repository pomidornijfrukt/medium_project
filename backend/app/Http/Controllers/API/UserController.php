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
}
