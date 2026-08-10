<?php

namespace App\Enums;

enum VenuePlanChangeStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Canceled = 'canceled';
}
