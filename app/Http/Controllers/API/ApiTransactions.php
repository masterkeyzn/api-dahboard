<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Moneysite\ApiGame;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiTransactions extends Controller
{
    public static function sendRequest(string $endpoint, array $data): array
    {
        $api = ApiGame::firstOrFail();

        $payload = array_merge([
            'agent_code'  => $api->agent_code,
            'agent_token' => $api->agent_token,
        ], $data);

        try {
            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->acceptJson()
                ->withHeaders([
                    'X-Agent-Code' => $api->agent_code,
                    'X-Secret-Key' => $api->agent_token,
                ])
                ->post("{$api->api_url}$endpoint", $data);

            $json = $response->json();

            if ($json === null) {
                Log::warning("API Response not JSON", [
                    'endpoint' => $endpoint,
                    'payload'  => $payload,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Invalid or empty JSON response',
                    'status'  => $response->status(),
                    'body'    => $response->body(),
                ];
            }

            return $json;

        } catch (\Throwable $e) {
            Log::error("API Request failed", [
                'endpoint' => $endpoint,
                'payload'  => $payload,
                'api_url'  => $api->api_url,
                'error'    => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Request failed: ' . $e->getMessage(),
            ];
        }
    }

    public static function deposit(string $userCode, int $amount): array
    {
        $endpoint = '/user/transfer';

        return self::formatResponse(
            self::sendRequest($endpoint, [
                'user_code' => $userCode,
                'amount'    => $amount,
            ])
        );
    }

    public static function withdraw(string $userCode, int $amount): array
    {
        $endpoint = '/user/transfer';

        return self::formatResponse(
            self::sendRequest($endpoint, [
                'user_code' => $userCode,
                'amount'    => -$amount,
            ])
        );
    }

    public static function getBalance(): array
    {
        $endpoint = '/agent/info';

        return self::formatResponse(
            self::sendRequest($endpoint, [])
        );
    }

    private static function formatResponse(array $response): array
    {
        return [
            'success' => ($response['success'] ?? false) == true,
            'message' => $response['message'] ?? 'FAILED',
            'data'    => $response['data'] ?? null,
        ];
    }
}
