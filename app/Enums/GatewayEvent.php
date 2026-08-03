<?php

namespace App\Enums;

/**
 * Anticorruption layer for the payment gateway webhook vocabulary.
 *
 * Every event the gateway is able to emit must have a case here. Anything the
 * gateway sends that is not mapped is explicitly logged and ignored instead of
 * being silently coerced into a local status.
 */
enum GatewayEvent: string
{
    case PaymentCreated = 'PAYMENT_CREATED';
    case PaymentUpdated = 'PAYMENT_UPDATED';
    case PaymentRestored = 'PAYMENT_RESTORED';
    case PaymentConfirmed = 'PAYMENT_CONFIRMED';
    case PaymentReceived = 'PAYMENT_RECEIVED';
    case PaymentAnticipated = 'PAYMENT_ANTICIPATED';
    case PaymentOverdue = 'PAYMENT_OVERDUE';
    case PaymentDeleted = 'PAYMENT_DELETED';
    case PaymentRefunded = 'PAYMENT_REFUNDED';
    case PaymentPartiallyRefunded = 'PAYMENT_PARTIALLY_REFUNDED';
    case PaymentRefundInProgress = 'PAYMENT_REFUND_IN_PROGRESS';
    case PaymentRefundDenied = 'PAYMENT_REFUND_DENIED';
    case PaymentReceivedInCashUndone = 'PAYMENT_RECEIVED_IN_CASH_UNDONE';
    case PaymentCreditCardCaptureRefused = 'PAYMENT_CREDIT_CARD_CAPTURE_REFUSED';
    case PaymentAuthorized = 'PAYMENT_AUTHORIZED';
    case PaymentAwaitingRiskAnalysis = 'PAYMENT_AWAITING_RISK_ANALYSIS';
    case PaymentApprovedByRiskAnalysis = 'PAYMENT_APPROVED_BY_RISK_ANALYSIS';
    case PaymentReprovedByRiskAnalysis = 'PAYMENT_REPROVED_BY_RISK_ANALYSIS';
    case PaymentChargebackRequested = 'PAYMENT_CHARGEBACK_REQUESTED';
    case PaymentChargebackDispute = 'PAYMENT_CHARGEBACK_DISPUTE';
    case PaymentAwaitingChargebackReversal = 'PAYMENT_AWAITING_CHARGEBACK_REVERSAL';
    case PaymentDunningRequested = 'PAYMENT_DUNNING_REQUESTED';
    case PaymentDunningReceived = 'PAYMENT_DUNNING_RECEIVED';
    case PaymentBankSlipViewed = 'PAYMENT_BANK_SLIP_VIEWED';
    case PaymentBankSlipCancelled = 'PAYMENT_BANK_SLIP_CANCELLED';
    case PaymentCheckoutViewed = 'PAYMENT_CHECKOUT_VIEWED';
    case PaymentSplitCancelled = 'PAYMENT_SPLIT_CANCELLED';
    case PaymentSplitDivergenceBlock = 'PAYMENT_SPLIT_DIVERGENCE_BLOCK';
    case PaymentSplitDivergenceBlockFinished = 'PAYMENT_SPLIT_DIVERGENCE_BLOCK_FINISHED';
    case AccessTokenExpiringSoon = 'ACCESS_TOKEN_EXPIRING_SOON';

    /**
     * Fallback for gateways (and legacy payloads) that only report the
     * normalized payment status instead of a named event.
     */
    public static function fromNormalizedStatus(string $status): ?self
    {
        return match ($status) {
            'paid' => self::PaymentConfirmed,
            'refunded' => self::PaymentRefunded,
            'overdue' => self::PaymentOverdue,
            'failed' => self::PaymentCreditCardCaptureRefused,
            'pending' => self::PaymentCreated,
            default => null,
        };
    }

    /**
     * Events that carry no financial meaning for us. They are acknowledged and
     * dropped without touching the invoice.
     */
    public function isInformational(): bool
    {
        return in_array($this, [
            self::PaymentAnticipated,
            self::PaymentAuthorized,
            self::PaymentAwaitingRiskAnalysis,
            self::PaymentRefundInProgress,
            self::PaymentRefundDenied,
            self::PaymentDunningRequested,
            self::PaymentDunningReceived,
            self::PaymentBankSlipViewed,
            self::PaymentBankSlipCancelled,
            self::PaymentCheckoutViewed,
            self::PaymentSplitCancelled,
            self::PaymentSplitDivergenceBlock,
            self::PaymentSplitDivergenceBlockFinished,
        ], true);
    }

    /**
     * Chargeback events must revoke access and alert the backoffice, so they
     * are grouped explicitly instead of being lumped in with refunds.
     */
    public function isChargeback(): bool
    {
        return in_array($this, [
            self::PaymentChargebackRequested,
            self::PaymentChargebackDispute,
            self::PaymentAwaitingChargebackReversal,
        ], true);
    }

    /**
     * Local invoice status this event should drive the invoice to, or null
     * when the event does not by itself determine a status.
     */
    public function targetInvoiceStatus(): ?InvoiceStatus
    {
        return match ($this) {
            self::PaymentConfirmed,
            self::PaymentReceived,
            self::PaymentApprovedByRiskAnalysis => InvoiceStatus::Paid,
            self::PaymentOverdue => InvoiceStatus::Overdue,
            self::PaymentDeleted => InvoiceStatus::Canceled,
            self::PaymentRefunded,
            self::PaymentPartiallyRefunded,
            self::PaymentReceivedInCashUndone => InvoiceStatus::Refunded,
            self::PaymentChargebackRequested,
            self::PaymentChargebackDispute => InvoiceStatus::Disputed,
            self::PaymentAwaitingChargebackReversal => InvoiceStatus::ChargedBack,
            default => null,
        };
    }
}
