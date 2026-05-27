<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
}
