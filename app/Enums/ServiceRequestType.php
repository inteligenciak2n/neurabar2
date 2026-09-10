<?php

namespace App\Enums;

enum ServiceRequestType: string
{
    case Message = 'message';
    case CallToOrder = 'call_to_order';
    case Checkout = 'checkout';
}
