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
            'phone' => 'nullable|string',
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
    $request->validate([
        'login' => 'required|string', // can be email or phone
        'password' => 'required|string',
    ]);

    $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

    if (!Auth::attempt([$loginField => $request->login, 'password' => $request->password])) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $user = Auth::user();
    $token = $user->createToken('api_token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful',
        'token' => $token,
        'user' => $user,
    ]);
}


    // 🚪 Logout
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
