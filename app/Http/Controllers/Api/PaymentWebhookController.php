<?php

namespace App\Http\Controllers\Api;

use App\Actions\Subscription\ProcessWebhookPaymentAction;
use App\Exceptions\Subscription\InvalidWebhookTokenException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class PaymentWebhookController extends Controller
{
    public function __construct(private readonly ProcessWebhookPaymentAction $action) {}

    public function __invoke(Request $request, string $gateway): JsonResponse
    {
        $token = $this->resolveToken($request, $gateway);

        try {
            $result = $this->action->execute($gateway, (string) $token, $request->all());

            return response()->json([
                'received' => true,
                'gateway_payment_id' => $result['gateway_payment_id'] ?? null,
                'status' => $result['status'] ?? null,
            ]);
        } catch (InvalidWebhookTokenException $e) {
            Log::warning('Payment webhook unauthorized', ['gateway' => $gateway, 'error' => $e->getMessage()]);

            return response()->json(['received' => false, 'error' => $e->getMessage()], 401);
        } catch (InvalidArgumentException $e) {
            Log::warning('Payment webhook rejected', ['gateway' => $gateway, 'error' => $e->getMessage()]);

            return response()->json(['received' => false, 'error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            Log::error('Payment webhook failed', ['gateway' => $gateway, 'error' => $e->getMessage()]);

            return response()->json(['received' => false, 'error' => 'Internal error'], 500);
        }
    }

    /**
     * Each gateway carries the webhook token in its own header. Asaas sends it
     * in `asaas-access-token`, never as a bearer token — reading only the
     * bearer token made every real delivery fail with 401.
     */
    private function resolveToken(Request $request, string $gateway): ?string
    {
        /** @var list<string> $headers */
        $headers = (array) config("subscription.payment.webhook_token_headers.{$gateway}", []);
        $headers[] = 'X-Webhook-Token';

        foreach ($headers as $header) {
            $value = $request->header($header);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return $request->bearerToken();
    }
}
