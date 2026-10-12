<?php

namespace App\Enums;

enum DriverAssignmentStatus: string
{
    case Offered = 'offered';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case PickedUp = 'picked_up';
    case Delivered = 'delivered';
    case Expired = 'expired';
}
