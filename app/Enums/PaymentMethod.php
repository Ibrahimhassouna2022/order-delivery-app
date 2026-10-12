<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CashOnDelivery = 'cash_on_delivery';
    case Card = 'card';
    case EWallet = 'e_wallet';
}
