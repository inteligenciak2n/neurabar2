<?php

namespace App\Enums;

enum ModuleStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case Canceled = 'canceled';
}
