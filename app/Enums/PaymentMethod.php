<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CashOnDelivery = 'cash_on_delivery';
    case BankTransfer = 'bank_transfer';
    case Card = 'card';
    case EWallet = 'e_wallet';
}
