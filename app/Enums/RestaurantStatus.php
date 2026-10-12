<?php

namespace App\Enums;

enum RestaurantStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
}
