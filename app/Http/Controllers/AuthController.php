<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Notifications\ForgotPasswordNotification;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Notifications\ResetPasswordNotification;

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

            $token = $user->createToken(env("AUTH_TOKEN_HASH"))->plainTextToken;

            return response()->json([
                "user" => auth()->user(),
                "access_token" => $token,
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

    public function forgotPassword(Request $request)
    {
        $token = Str::random(40);

        $url = url(env("FRONTEND_URL") . "/reset-password?token=" . $token);

        $credentials = $request->validate(['email' => 'required|email']);

        $user = User::firstWhere("email", $credentials["email"]);

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => "Email Not Found",
            ]);
        }

        DB::transaction(function () use ($user, $token, $url) {
            DB::table('password_resets')
                ->updateOrInsert(
                    ['email' => $user->email],
                    ['token' => $token, "created_at" => Carbon::now()->format("Y-m-d H:m:s")]
                );

            Notification::sendNow($user, new ForgotPasswordNotification($url));
        });

        return response()->json([
            'message' => "Check your mail"
        ], Response::HTTP_OK);
    }

    public function resetPassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required',
        ]);

        if ($validator->fails()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, $validator->errors());
        }

        $credentials = $validator->validated();

        $user = User::firstWhere("email", $credentials["email"]);

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        $reset_credentials =  DB::table('password_resets')
            ->where(
                [
                    'email' => $credentials["email"],
                    'token' => $credentials["token"],
                ],
            );

        $reset_token =  $reset_credentials->first();

        if (!$reset_token) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        DB::transaction(function () use ($user, $reset_credentials, $credentials) {
            $user->forceFill([
                'password' => Hash::make($credentials["password"])
            ])->setRememberToken(Str::random(40));

            $user->save();

            $reset_credentials->delete();

            $url = url(env("FRONTEND_URL") . "/login");
            Notification::sendNow($user, new ResetPasswordNotification($url));
        });

        return response()->json([
            'message' => "Your password reset successful"
        ], Response::HTTP_OK);
    }
}
