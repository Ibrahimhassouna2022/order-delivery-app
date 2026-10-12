<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Driver = 'driver';
    case Restaurant = 'restaurant';
    case Admin = 'admin';
}
