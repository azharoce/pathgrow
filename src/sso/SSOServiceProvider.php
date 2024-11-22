<?php

namespace Studypath\SSO;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class SSOServiceProvider extends ServiceProvider
{

    public static function login(Request $request)
    {
        $request->session()->put("state", $state = Str::random(40));
        $query = http_build_query([
            "client_id" => env('SSO_CLIENT_ID'),
            "redirect_uri" => env('SSO_REDIRECT_URI'),
            "response_type" => "code",
            "scope" => "",
            "state" => $state,
        ]);


        return redirect(env('SSO_HOST') . "/oauth/authorize?" . $query);
    }
    public static function callback(Request $request)
    {
        $state = $request->session()->pull("state");
        throw_unless(strlen($state) > 0 && $state == $request->state, InvalidArgumentException::class);

        $response = Http::asForm()->post(env('SSO_HOST') . "/oauth/token", [
            "grant_type" => "authorization_code",
            "client_id" => env('SSO_CLIENT_ID'),
            "client_secret" => env('SSO_CLIENT_SECRET'),
            "redirect_uri" => env('SSO_REDIRECT_URI'),
            "code" => $request->code,
        ]);
        $request->session()->put($response->json());
        return redirect(route("sso.account"));
    }
    public static function account(Request $request)
    {
        $access_token = $request->session()->get("access_token");
        $response = Http::withHeaders([
            "Accept" => "application/json",
            "Authorization" => "Bearer " . $access_token,
        ])->get(env('SSO_HOST') . "/api/user");

        $dataUser = $response->json();
        try {
            $email = $dataUser["email"];
        } catch (\Throwable $th) {
            return redirect("login")->withErrors("Failed get data information");
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = new User;
            $user->name = $dataUser['name'];
            $user->email = $dataUser['email'];
            $user->email_verified_at = $dataUser['email_verified_at'];
            $user->save();
        }
        Auth::login($user);
        $request->session()->put('data', $response->json());
        return redirect(route("home"));
    }
}
