<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CinetPayService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $apiPassword;

    protected const TOKEN_CACHE_KEY = 'cinetpay_access_token';

    public function __construct()
    {
        $this->baseUrl = rtrim(config('cinetpay.base_url'), '/');
        $this->apiKey = (string) config('cinetpay.api_key');
        $this->apiPassword = (string) config('cinetpay.api_password');
    }

    /**
     * Initialize a web payment transaction. Returns CinetPay's raw response payload,
     * which includes `payment_url` (to redirect the client to) and `transaction_id`.
     */
    public function initializePayment(array $params): array
    {
        $payload = array_merge([
            'currency' => config('cinetpay.currency'),
            'lang' => config('cinetpay.lang'),
            'success_url' => config('cinetpay.success_url'),
            'failed_url' => config('cinetpay.failed_url'),
            'notify_url' => config('cinetpay.notify_url'),
        ], $params);

        $response = $this->authenticatedRequest('post', '/v1/payment', $payload);

        return $response->json();
    }

    /**
     * Re-verify a transaction's status directly with CinetPay (never trust a notify_url
     * payload without this check — it can be spoofed by a third party).
     */
    public function checkStatus(string $transactionId): array
    {
        $path = trim(config('cinetpay.check_status_path'), '/');

        $response = $this->authenticatedRequest('get', "/v1/{$path}/{$transactionId}");

        return $response->json();
    }

    /**
     * Perform an authenticated request, retrying once with a fresh token on 401.
     */
    protected function authenticatedRequest(string $method, string $path, array $payload = []): Response
    {
        $token = $this->getAccessToken();

        $response = $this->request($token, $method, $path, $payload);

        if ($response->status() === 401) {
            $token = $this->getAccessToken(forceRefresh: true);
            $response = $this->request($token, $method, $path, $payload);
        }

        if ($response->failed()) {
            Log::error('CinetPay API error', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new \RuntimeException($this->extractErrorMessage($response));
        }

        return $response;
    }

    protected function extractErrorMessage(Response $response): string
    {
        $body = $response->json();

        return $body['description'] ?? $body['message'] ?? "Erreur CinetPay (HTTP {$response->status()}).";
    }

    protected function request(string $token, string $method, string $path, array $payload): Response
    {
        $request = Http::withToken($token)->acceptJson();

        return $method === 'get'
            ? $request->get("{$this->baseUrl}{$path}")
            : $request->post("{$this->baseUrl}{$path}", $payload);
    }

    /**
     * Get a cached CinetPay access token, or log in to obtain a new one.
     */
    protected function getAccessToken(bool $forceRefresh = false): string
    {
        if (!$forceRefresh && Cache::has(self::TOKEN_CACHE_KEY)) {
            return Cache::get(self::TOKEN_CACHE_KEY);
        }

        $response = Http::acceptJson()->post("{$this->baseUrl}/v1/oauth/login", [
            'api_key' => $this->apiKey,
            'api_password' => $this->apiPassword,
        ]);

        if ($response->failed()) {
            Log::error('CinetPay login error', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new \RuntimeException($this->extractErrorMessage($response));
        }

        $data = $response->json();
        $token = $data['access_token'];
        $ttl = max((int) ($data['expires_in'] ?? 3600) - 60, 60);

        Cache::put(self::TOKEN_CACHE_KEY, $token, $ttl);

        return $token;
    }
}
