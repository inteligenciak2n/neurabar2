<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Open = 'open';
    case InPreparation = 'in_preparation';
    case Ready = 'ready';
    case Delivered = 'delivered';
}
