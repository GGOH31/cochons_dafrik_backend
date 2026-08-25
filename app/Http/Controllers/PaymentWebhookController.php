<?php

namespace App\Http\Controllers;

use App\Services\ClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(protected ClientService $clientService)
    {
    }

    /**
     * Server-to-server notification from CinetPay once a payment is confirmed SUCCESS or FAILED.
     * We never trust the payload's status directly: it re-verifies with CinetPay's API before
     * touching the order/escrow (see ClientService::verifyCinetPayPayment).
     */
    public function cinetpayNotify(Request $request)
    {
        Log::info('CinetPay notify received', $request->all());

        $transactionId = $request->input('transaction_id')
            ?? $request->input('cpm_trans_id')
            ?? $request->input('id');

        if (!$transactionId) {
            return response('missing transaction id', 400);
        }

        try {
            $this->clientService->verifyCinetPayPayment($transactionId);
        } catch (\Exception $e) {
            Log::error('CinetPay notify processing failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            // Non-2xx so CinetPay retries the notification later.
            return response('error', 500);
        }

        return response('OK', 200);
    }

    /**
     * Landing page the client's browser/WebView is redirected to after a successful payment.
     * This is informational only — the authoritative status change happens via the webhook
     * above (or the app's manual verify call), never from this redirect alone.
     */
    public function success(Request $request)
    {
        return response(
            '<html><body><h3>Paiement effectué avec succès. Vous pouvez retourner à l\'application.</h3></body></html>'
        )->header('Content-Type', 'text/html');
    }

    /**
     * Landing page after a failed or cancelled payment.
     */
    public function failed(Request $request)
    {
        return response(
            '<html><body><h3>Paiement échoué ou annulé. Vous pouvez retourner à l\'application pour réessayer.</h3></body></html>'
        )->header('Content-Type', 'text/html');
    }
}
