<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Moneysite\ApiGame;
use Illuminate\Support\Facades\Http;

class ApiTransactions extends Controller
{
    public static function sendRequest(string $endpoint, array $data): array
    {
        $api = ApiGame::firstOrFail();

        $payload = array_merge([
            'agent_code' => $api->agent_code,
            'agent_token' => $api->agent_token,
        ], $data);

        try {
            return Http::timeout(15)
                ->connectTimeout(5)
                ->acceptJson()
                ->post("{$api->api_url}$endpoint", $payload)
                ->json();
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Request failed: ' . $e->getMessage()
            ];
        }
    }

    public static function deposit(string $userCode, int $amount): array
    {
        $endpoint = '/api/v2/user_deposit';

        return self::formatResponse(
            self::sendRequest($endpoint, [
                'user_code' => $userCode,
                'amount' => $amount,
            ])
        );
    }

    public static function getBalance(): array
    {
        $endpoint = '/api/v2/info';

        return self::formatResponse(
            self::sendRequest($endpoint, [])
        );
    }

    public static function resetUserBalance(): array
    {
        $endpoint = '/api/v2/user_withdraw_all';

        return self::formatResponse(
            self::sendRequest($endpoint, [])
        );
    }

    public static function withdraw(string $userCode, ?int $amount = null): array
    {
        $endpoint = '/api/v2/user_withdraw';

        $payload = ['user_code' => $userCode];

        if (!$amount === null) {
            $payload['amount'] = $amount;
        }

        return self::formatResponse(
            self::sendRequest($endpoint, $payload)
        );
    }

    private static function formatResponse(array $response): array
    {
        return [
            'success' => ($response['status'] ?? 0) == 1,
            'message' => $response['msg'] ?? 'FAILED',
            'data' => $response,
        ];
    }
}
