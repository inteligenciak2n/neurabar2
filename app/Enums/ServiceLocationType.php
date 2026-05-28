<?php

namespace App\Enums;

enum ServiceLocationType: string
{
    case Table = 'table';
    case Bar = 'bar';
    case Area = 'area';
    case Delivery = 'delivery';
    case Takeaway = 'takeaway';
}
