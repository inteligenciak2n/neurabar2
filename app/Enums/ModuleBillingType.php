<?php

namespace App\Enums;

enum ModuleBillingType: string
{
    case Fixed = 'fixed';
    case Metered = 'metered';
    case Hybrid = 'hybrid';
}
