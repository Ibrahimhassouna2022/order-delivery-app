<?php

namespace App\Actions\Restaurant;

use RuntimeException;

/**
 * Raised when a restaurant order step is attempted out of sequence or on a
 * terminal order (research R-9).
 */
class OrderStatusConflictException extends RuntimeException {}
