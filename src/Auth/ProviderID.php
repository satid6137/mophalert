<?php
require_once __DIR__ . '/../Helpers/HttpClient.php';
require_once __DIR__ . '/../Helpers/Logger.php';
require_once __DIR__ . '/../../config/env.php';

class ProviderID
{

    public static function getProviderToken($healthAccessToken)
    {
        $url = env('PROVIDER_ID_URL') . "/api/v1/services/token";

        $body = json_encode([
            "client_id" => env('PROVIDER_ID_CLIENT_ID'),
            "secret_key" => env('PROVIDER_ID_SECRET_KEY'),
            "token_by" => "Health ID",
            "token" => $healthAccessToken
        ]);

        $headers = ["Content-Type: application/json"];

        Logger::write("ProviderID: Request Token");

        list($response, $error) = HttpClient::post($url, $headers, $body);

        Logger::write("ProviderID: Response Token", ["response" => $response, "error" => $error]);

        return json_decode($response, true);
    }

    public static function getProfile($providerAccessToken)
    {
        $url = env('PROVIDER_ID_URL') . "/api/v1/services/profile";

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $providerAccessToken,
            "client-id: " . env('PROVIDER_ID_CLIENT_ID'),
            "secret-key: " . env('PROVIDER_ID_SECRET_KEY')
        ];

        Logger::write("ProviderID: Request Profile");

        list($response, $error) = HttpClient::get($url, $headers);

        Logger::write("ProviderID: Response Profile", ["response" => $response, "error" => $error]);

        return json_decode($response, true);
    }
}
