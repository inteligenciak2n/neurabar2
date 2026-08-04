<?php

namespace App\Http\Controllers\Settings;

use App\Enums\PaymentSaasMethod;
use App\Exceptions\Subscription\GatewayRequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PayInvoiceRequest;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\PaymentAttempt;
use App\Models\Tenant\VenueInvoice;
use App\Services\Subscription\PaymentSaasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class SubscriptionInvoiceController extends Controller
{
    public function __construct(private readonly PaymentSaasService $paymentService) {}

    public function index(Request $request): Response
    {
        Gate::authorize('manage-subscription');

        $user = $request->user();
        $venue = $user?->currentVenue;

        if (! $venue?->corporation) {
            abort(403, 'No corporation context found.');
        }

        $corporation = $venue->corporation;
        $venueIds = $corporation->venues->pluck('id');

        $venueInvoices = VenueInvoice::query()
            ->whereIn('venue_id', $venueIds)
            ->with('venue:id,name')
            ->orderByDesc('period')
            ->orderByDesc('due_date')
            ->paginate(20, ['*'], 'venue_page')
            ->withQueryString();

        $corporationInvoices = CorporationInvoice::query()
            ->where('corporation_id', $corporation->id)
            ->orderByDesc('period')
            ->orderByDesc('due_date')
            ->paginate(20, ['*'], 'corporation_page')
            ->withQueryString();

        return Inertia::render('Settings/Subscription/Invoices', [
            'venueInvoices' => $venueInvoices,
            'corporationInvoices' => $corporationInvoices,
            'paymentMethods' => $user->paymentMethods()
                ->orderByDesc('is_default')
                ->orderByDesc('created_at')
                ->get(['id', 'brand', 'last4', 'holder_name', 'is_default', 'expiration_month', 'expiration_year']),
            'paymentMethodOptions' => PaymentSaasMethod::options(),
            'filters' => $request->only('period', 'status'),
        ]);
    }

    public function pay(PayInvoiceRequest $request, string $invoiceType, string $invoiceId): RedirectResponse
    {
        Gate::authorize('manage-subscription');

        $invoice = $this->resolveInvoice($request, $invoiceType, $invoiceId);

        if (! $invoice) {
            abort(403, 'Invoice not found or not accessible.');
        }

        Gate::authorize('pay', $invoice);

        try {
            $result = $this->paymentService->charge($invoice, $request->validated(), $request->user());
        } catch (GatewayRequestException $exception) {
            return back()->with('error', $exception->userMessage());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $message = match ($result['status']) {
            'paid' => __('Payment confirmed successfully.'),
            'pending' => __('Payment initiated. Complete the instructions to finalize.'),
            'failed' => __('Payment failed. Please try again or use another method.'),
            default => $result['message'],
        };

        // PIX e boleto exigem uma ação fora do sistema: sem as instruções o
        // cliente saa da tela sem saber como concluir o pagamento.
        if ($result['status'] === 'pending') {
            return redirect()->route('settings.subscription.invoices.show', [
                'invoiceType' => $invoiceType,
                'invoiceId' => $invoiceId,
            ])->with('success', $message);
        }

        return back()->with($result['status'] === 'failed' ? 'error' : 'success', $message);
    }

    public function show(Request $request, string $invoiceType, string $invoiceId): Response
    {
        Gate::authorize('manage-subscription');

        $invoice = $this->resolveInvoice($request, $invoiceType, $invoiceId);

        if (! $invoice) {
            abort(403, 'Invoice not found or not accessible.');
        }

        Gate::authorize('view', $invoice);

        $invoice->load('items');

        if ($invoice instanceof VenueInvoice) {
            $invoice->load('venue:id,name');
        }

        return Inertia::render('Settings/Subscription/InvoiceShow', [
            'invoice' => $invoice,
            'type' => $invoiceType,
            'paymentInstructions' => $this->paymentInstructions($invoice, $invoiceType),
        ]);
    }

    /**
     * Dados que o cliente precisa para concluir um PIX ou boleto já emitido.
     *
     * Apenas os campos públicos da cobrança são expostos: o payload bruto do
     * gateway carrega dados do cliente e do meio de pagamento.
     *
     * @return array<string, string>|null
     */
    private function paymentInstructions(VenueInvoice|CorporationInvoice $invoice, string $invoiceType): ?array
    {
        if ($invoice->status->isFinalized()) {
            return null;
        }

        $attempt = PaymentAttempt::query()
            ->where('invoice_type', $invoiceType)
            ->where('invoice_id', $invoice->id)
            ->where('status', 'pending')
            ->orderByDesc('attempted_at')
            ->first();

        if (! $attempt) {
            return null;
        }

        $payload = is_array($attempt->payload) ? $attempt->payload : [];

        $instructions = array_filter([
            'pix_code' => $payload['pixQrCode'] ?? null,
            'pix_qr_image' => $payload['pixQrCodeImage'] ?? null,
            'boleto_url' => $payload['bankSlipUrl'] ?? null,
            'invoice_url' => $payload['invoiceUrl'] ?? null,
            'due_date' => $payload['dueDate'] ?? null,
        ], fn ($value): bool => is_string($value) && $value !== '');

        return $instructions === [] ? null : $instructions;
    }

    private function resolveInvoice(Request $request, string $type, string $id): VenueInvoice|CorporationInvoice|null
    {
        $user = $request->user();
        $corporation = $user?->currentVenue?->corporation;

        if (! $corporation) {
            return null;
        }

        if ($type === 'corporation') {
            return CorporationInvoice::query()
                ->where('corporation_id', $corporation->id)
                ->find($id);
        }

        $venueIds = $corporation->venues->pluck('id');

        return VenueInvoice::query()
            ->whereIn('venue_id', $venueIds)
            ->find($id);
    }
}
