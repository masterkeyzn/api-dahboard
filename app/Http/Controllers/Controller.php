<?php

namespace App\Http\Controllers;

use App\Models\Moneysite\ApiGame;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class Controller
{
    public static function sendToNexus(array $postArray)
    {
        try {
            $api = ApiGame::first();

            $postArray['agent_code'] = $api->agent_code;
            $postArray['agent_token'] = $api->agent_token;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
                ->timeout(5)
                ->post($api->api_url ?? 'https://api.nexusggr.com', $postArray);

            $result = $response->json();

            if ($response->successful()) {
                if (isset($result['status']) && $result['status'] === 1) {
                    return $result;
                } else {
                    Log::warning('Nexus API responded with failure', [
                        'request' => $postArray,
                        'response' => $result,
                    ]);

                    return [
                        'status' => 0,
                        'msg' => $result['msg'] ?? 'Unknown error from Nexus API.',
                    ];
                }
            } else {
                Log::error('Nexus API HTTP failure', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'request' => $postArray,
                ]);

                return [
                    'status' => 0,
                    'msg' => 'HTTP error from Nexus API.',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception while calling Nexus API', [
                'message' => $e->getMessage(),
                'request' => $postArray,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 0,
                'msg' => $e->getMessage(),
            ];
        }
    }

    protected function apiResponse(bool $success, string $message, int $code, array $data = [])
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => (object) $data,
        ], $code);
    }
}
