<?php

namespace App\Enums;

enum ServiceRequestStatus: string
{
    case Pending = 'pending';
    case Acknowledged = 'acknowledged';
    case Resolved = 'resolved';
}
