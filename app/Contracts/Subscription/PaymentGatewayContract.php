<?php

namespace App\Contracts\Subscription;

use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\VenueInvoice;

interface PaymentGatewayContract
{
    /**
     * Create or update a customer in the gateway.
     *
     * @return string The gateway customer identifier.
     */
    public function createCustomer(array $data): string;

    /**
     * Tokenize a credit card and return a safe token.
     *
     * @return array{gateway_token: string, brand: string, last4: string}
     */
    public function saveCard(string $customerId, array $cardData): array;

    /**
     * Charge an invoice using the provided payment data.
     *
     * @return array{status: string, gateway_payment_id: string, message: string, payload: array<string, mixed>}
     */
    public function chargeInvoice(VenueInvoice|CorporationInvoice $invoice, array $paymentData): array;

    /**
     * Generate PIX payload for an invoice.
     *
     * @return array{status: string, gateway_payment_id: string, qr_code: string, qr_code_image: string|null, expires_at: string, message: string, payload: array<string, mixed>}
     */
    public function processPix(VenueInvoice|CorporationInvoice $invoice): array;

    /**
     * Generate boleto payload for an invoice.
     *
     * @return array{status: string, gateway_payment_id: string, boleto_url: string, barcode: string, due_date: string, message: string, payload: array<string, mixed>}
     */
    public function processBoleto(VenueInvoice|CorporationInvoice $invoice): array;

    /**
     * Handle an incoming webhook payload.
     *
     * @return array{gateway_payment_id: string, status: string, invoice_type: string, invoice_id: string, amount: float, payload: array<string, mixed>}
     */
    public function handleWebhook(string $gateway, array $payload): array;
}
