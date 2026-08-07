<?php

namespace App\Services\Subscription\Webhook\Handlers;

use App\Services\Subscription\Webhook\WebhookContext;

interface WebhookEventHandler
{
    public function handle(WebhookContext $context): void;
}
