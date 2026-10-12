<?php

namespace App\Enums;

enum WalletEntryType: string
{
    case DeliveryCredit = 'delivery_credit';
    case PayoutDebit = 'payout_debit';
    case Adjustment = 'adjustment';
}
