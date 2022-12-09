<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::firstWhere('email', $credentials["email"]);

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if (!Hash::check($credentials["password"], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password does not match our records.']
            ]);
        }

        if (Auth::guard('web')->attempt($credentials)) {
            $request->session()->regenerate();

            return response()->json([
                "user" => auth()->user(),
                "access_token" => $user->createToken(env("AUTH_TOKEN_HASH"))->plainTextToken,
                "expiration" => (int)config("sanctum.expiration")
            ], Response::HTTP_OK);
        } else {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }
    }


    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        auth()->user()->tokens()->delete();
    }
}
