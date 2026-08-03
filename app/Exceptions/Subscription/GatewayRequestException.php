<?php

namespace App\Exceptions\Subscription;

use RuntimeException;

/**
 * The exception message carries the raw gateway description and is meant for
 * logs only. Never render it to the end user: it leaks gateway internals and
 * may echo back fields submitted in the request.
 */
class GatewayRequestException extends RuntimeException
{
    public function __construct(string $message, private readonly ?string $errorCode = null)
    {
        parent::__construct($message);
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Generic, translated message safe to show to the end user.
     */
    public function userMessage(): string
    {
        return __('We could not complete the operation with the payment provider. Please try again in a few minutes.');
    }
}
