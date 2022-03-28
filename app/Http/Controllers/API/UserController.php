<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'phone' => 'nullable|string|max:255',
                'password' => 'required|string|min:8'
            ]);

            User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password)
            ]);

            $user = User::where('email', $request->email)->first();

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user,
            ],
            'User registered successfully.'
            );
            
        } catch (\Exception $e) {
            return ResponseFormatter::error(
                [
                    'message' => 'Something went wrong.',
                    'error' => $e->getMessage(),
                ],
                'Authentication failed.',
                500
            );
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $credentials = $request->only('email', 'password');

            if (!Auth::attempt($credentials)) {
                return ResponseFormatter::error(
                    [
                        'message' => 'Unauthorized.',
                    ],
                    'Authentication failed.',
                    401
                );
            }

            $user = User::where('email', $request->email)->first();

            if(!Hash::check($request->password, $user->password,[])) {
                throw new \Exception('Invalid credentials.');
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user,
            ],
            'User logged in successfully.'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::error(
                [
                    'message' => 'Something went wrong.',
                    'error' => $e->getMessage(),
                ],
                'Authentication failed.',
                500
            );
        }
    }

    public function fetch(Request $request)
    {
        return ResponseFormatter::success([
            'user' => Auth::user(),
        ],
        'User fetched successfully.'
        );
    }

    public function updateProfile(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255',
                'email' => 'required|string|email|max:255',
                'phone' => 'nullable|string|max:255',
                'password' => 'nullable|string|min:8'
            ]);

            $user = Auth::user();

            $user->name = $request->name;
            $user->username = $request->username;
            $user->email = $request->email;
            $user->phone = $request->phone;

            if($request->password) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            return ResponseFormatter::success([
                'user' => $user,
            ],
            'User updated successfully.'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::error(
                [
                    'message' => 'Something went wrong.',
                    'error' => $e->getMessage(),
                ],
                'Authentication failed.',
                500
            );
        }
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return ResponseFormatter::success([
            'message' => 'User logged out successfully.',
        ],
        'User logged out successfully.'
        );
    }
}
