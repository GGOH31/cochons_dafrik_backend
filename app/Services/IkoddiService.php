<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class IkoddiService
{
    protected string $apiKey;
    protected string $groupId;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('ikoddi.api_key');
        $this->groupId = config('ikoddi.organization_id');
        $this->baseUrl = config('ikoddi.base_url');
    }

    /**
     * Envoyer un SMS à un ou plusieurs destinataires
     *
     * @param array $sentTo Numéros au format international sans le +, ex: ["22670707070"]
     * @param string $message
     * @param string $from Sender ID (par défaut "Ikoddi" sauf si vous avez un Sender ID validé)
     * @param string $countryStringCode Ex: "BF"
     * @param string $countryNumberCode Ex: "226"
     */
    public function sendSms(
        array $sentTo,
        string $message,
        string $from = 'Ikoddi',
        string $countryStringCode = 'CI',
        string $countryNumberCode = '225',
        ?string $campaignName = null
    ): array {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/groups/{$this->groupId}/sms", [
                'sentTo' => $sentTo,
                'message' => $message,
                'from' => $from,
                'smsBroadCast' => $campaignName ?? 'default',
                'countryStringCode' => $countryStringCode,
                'countryNumberCode' => $countryNumberCode,
                'messageType' => 'sms',
            ]);

            $response->throw(); // lève une exception si erreur HTTP

            return $response->json();
        } catch (RequestException $e) {
            report($e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => $e->response?->status(),
            ];
        }
    }

    /**
     * Récupérer le solde SMS
     */
    public function getSmsBalance(): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
        ])->get("{$this->baseUrl}/groups/{$this->groupId}/sms/accounts/current/balance");

        return $response->json();
    }
}