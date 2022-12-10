<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Response;
use App\Notifications\VerificationMailNotification;


class UserController extends Controller
{
    public function index()
    {
        return auth()->user();
    }

    public function sendVerificationMail()
    {
        $user = Auth::user();
        $token = Str::random(40);

        if (!$user) {
            abort(Response::HTTP_UNAUTHORIZED, "Unauthorized");
        }

        DB::transaction(function () use ($user, $token) {
            $user->setRememberToken($token);

            $user->save();

            $url = url(env("FRONTEND_URL") . "/verify-email?token=" . $token);
            Notification::sendNow($user, new VerificationMailNotification($url));
        });

        return response()->json([
            'message' => "Check your mail"
        ], Response::HTTP_OK);
    }

    public function verifyMail(Request $request)
    {

        $user = Auth::user();
        $token = Str::random(40);

        if (!$user ||  $user->remember_token != $request->get('token')) {
            abort(Response::HTTP_UNAUTHORIZED, "Unauthorized");
        }

        $user->setRememberToken($token);
        $user->email_verified_at = Carbon::now()->format("Y-m-d H:m:s");

        $user->save();

        return response()->json([
            'message' => "Email Verified"
        ], Response::HTTP_OK);
    }
}
