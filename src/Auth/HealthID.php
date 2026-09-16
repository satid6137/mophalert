<?php
require_once __DIR__ . '/../Helpers/HttpClient.php';
require_once __DIR__ . '/../Helpers/Logger.php';
require_once __DIR__ . '/../../config/env.php';

class HealthID
{

    public static function loginUrl()
    {
        return env('HEALTH_ID_URL') . "/oauth/redirect?client_id=" . env('HEALTH_ID_CLIENT_ID')
            . "&redirect_uri=" . urlencode(env('HEALTH_ID_REDIRECT_URI'))
            . "&response_type=code";
    }

    public static function getAccessToken($code)
    {
        $url = env('HEALTH_ID_URL') . "/api/v1/token";

        $body = http_build_query([
            "grant_type" => "authorization_code",
            "code" => $code,
            "redirect_uri" => env('HEALTH_ID_REDIRECT_URI'),
            "client_id" => env('HEALTH_ID_CLIENT_ID'),
            "client_secret" => env('HEALTH_ID_CLIENT_SECRET')
        ]);

        $headers = ["Content-Type: application/x-www-form-urlencoded"];

        Logger::write("HealthID: Request Token", ["code" => $code]);

        list($response, $error) = HttpClient::post($url, $headers, $body);

        Logger::write("HealthID: Response Token", ["response" => $response, "error" => $error]);

        return json_decode($response, true);
    }
}
