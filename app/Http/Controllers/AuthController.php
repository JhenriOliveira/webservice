<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Invalid validation data',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Invalid validation data',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function logout(Request $request)
    {
        $fullToken = $request->bearerToken();
        $plainTextToken = $fullToken ? explode('|', $fullToken)[1] ?? $fullToken : null;

        if (!$plainTextToken) {
            return response()->json(['message' => 'Malformed token'], 401);
        }

        $hashedToken = hash('sha256', $plainTextToken);
        $tokenRecord = \Laravel\Sanctum\PersonalAccessToken::where('token', $hashedToken)->first();

        if (!$tokenRecord) {
            return response()->json(['message' => 'Token not found in database'], 401);
        }

        if ($tokenRecord->expires_at && now()->gt($tokenRecord->expires_at)) {
            return response()->json(['message' => 'Token expired'], 401);
        }

        try {
            $tokenRecord->delete();
            return response()->json(['message' => 'Logout successful']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function user(Request $request)
    {
        $fullToken = $request->bearerToken();

        if (!$fullToken) {
            return response()->json(['message' => 'Authorization token required'], 401);
        }

        $tokenParts = explode('|', $fullToken);
        $plainTextToken = end($tokenParts);

        $hashedToken = hash('sha256', $plainTextToken);
        $tokenModel = \Laravel\Sanctum\PersonalAccessToken::where('token', $hashedToken)->first();

        if (!$tokenModel) {
            return response()->json(['message' => 'Invalid or expired token'], 401);
        }

        $user = $tokenModel->tokenable;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }
}