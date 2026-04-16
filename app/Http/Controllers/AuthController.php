<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // 🧑 Register
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'phone'       => 'required|unique:users,phone',
            'role' => 'nullable|in:admin,staff,vendor,customer',
        ]);

        $data['password'] = bcrypt($data['password']);
        $data['role'] = $data['role'] ?? 'staff';

        $user = User::create($data);
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'Registered successfully',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    // 🔐 Login
   public function login(Request $request)
{
    try {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid phone or password',
                'success' => false,
            ], 401);
        }

        // ✅ CHECK USER ACTIVE STATUS
        if ($user->is_active != 1) {
            return response()->json([
                'message' => 'Your account is inactive. Please contact admin.',
                'success' => false,
            ], 403);
        }

        // ✅ CREATE TOKEN ONLY IF ACTIVE
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'success' => true,
            'token' => $token,
            'user' => $user,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred during login',
            'error' => $e->getMessage(),
            'success' => false,
        ], 500);
    }
}

    // 🚪 Logout
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
