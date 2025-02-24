<?php

namespace Pathgrow\SSO;

use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class SSOClientProvider extends ServiceProvider
{

    public static function login(Request $request)
    {
        $request->session()->put("state", $state = Str::random(40));
        $query = http_build_query([
            "client_id" => env('SSO_CLIENT_ID'),
            "redirect_uri" => env('SSO_REDIRECT_URI'),
            "response_type" => "code",
            "scope" => "",
            "state" => $state
        ]);
        return redirect(env('SSO_HOST') . "/oauth/authorize?" . $query);
    }

    public static function getAuthLink(Request $request): JsonResponse
    {
        $request->session()->put("state", $state = Str::random(40));
        $query = http_build_query([
            "client_id" => env('SSO_CLIENT_ID'),
            "redirect_uri" => env('SSO_REDIRECT_URI'),
            'response_type' => 'code',
            'scope' => '',
            'state' => $state,
        ]);

        return response()->json(['authorize_url' => env('SSO_HOST') . '/oauth/authorize?' . $query, 'state' => $state], 200);
    }

    private function getAuthToken($state, $code)
    {
        throw_unless(strlen($state) > 0 && $state, InvalidArgumentException::class);

        $response = Http::asForm()->post(
            env('SSO_HOST') . '/oauth/token',
            [
                'grant_type' => 'authorization_code',
                'client_id' => env('SSO_CLIENT_ID'),
                'client_secret' => env('SSO_CLIENT_SECRET'),
                'redirect_url' => env('SSO_REDIRECT_URI'),
                'code' => $code,
            ]
        );
        return $response->json()['access_token'];
    }
    public static function callback_api(Request $request)
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
        return $response->json();
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
        return redirect("");
    }
}
