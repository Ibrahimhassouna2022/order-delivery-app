<?php

namespace App\Enums;

enum RestaurantOnboardingMethod: string
{
    case Assisted = 'assisted';
    case SelfService = 'self_service';
}
