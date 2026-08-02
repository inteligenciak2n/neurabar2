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
        $token = $request->bearerToken() ?? $request->header('X-Webhook-Token');

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
}
