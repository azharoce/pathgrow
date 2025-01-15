<?php

namespace Pathgrow\SSO;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class SSOClientProvider extends ServiceProvider
{
    public static $client_id;
    public static $secret_key;
    public static $sso_redirect_uri;
    public static $app_url;

    public function __construct()
    {
        self::$client_id = getenv('APP_ID') ? ENV('APP_ID') : getenv('APP_ID');
        self::$secret_key = getenv('SECRET_KEY') ? ENV('SECRET_KEY') : getenv('SECRET_KEY');
        self::$sso_redirect_uri = getenv('APP_URL') . "/sso/callback" ? ENV('APP_URL') . "/sso/callback" : getenv('APP_URL') . "/sso/callback";
        self::$app_url = getenv('APP_URL') ? ENV('APP_URL') : getenv('APP_URL');
    }
    public static function checkingApps(Request $request)
    {
        $response = Http::withHeaders([
            "Accept" => "application/json",
            "apps-id" => self::$client_id,
        ])->post(env('SSO_HOST') . "/api/apps");
        $apps_status = $response->json();
        if ($apps_status['status'] == 1) {
            return true;
        };
        return false;
    }
    public static function login(Request $request)
    {
        $checkingApps = self::checkingApps($request);
        if ($checkingApps) {
            return redirect("/")->withErrors("Maaf aplikasi anda belum diaktifkan");
        }
        $request->session()->put("state", $state = Str::random(40));
        $query = http_build_query([
            "client_id" => self::$client_id,
            "redirect_uri" => self::$sso_redirect_uri,
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
            "client_id" => self::$client_id,
            "client_secret" => self::$secret_key,
            "redirect_uri" => self::$sso_redirect_uri,
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
            "apps-id" => self::$client_id,
        ])->get(env('SSO_HOST') . "/api/user");

        $dataUser = $response->json();
        try {
            $email = $dataUser["email"];
        } catch (\Throwable $th) {
            return redirect("login")->withErrors("Maaf akun ada tidak terdaftar di system SSO Kami.");
        }
        if (empty($dataUser['apps_id'])) {
            return redirect("/")->withErrors("Maaf Akun anda tidak boleh mengakses aplikasi ini.");
        }
        if ($dataUser['apps_id'] != self::$client_id) {
            return redirect("/")->withErrors("Maaf Akun anda tidak boleh mengakses aplikasi ini.");
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
        if ($user) {
            $token = $user->createToken('MyAppToken')->accessToken;
            $request->session()->put('api_token', $token);
        }
        $request->session()->put('data', $response->json());
        return redirect("");
    }
}
