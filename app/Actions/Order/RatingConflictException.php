<?php

namespace App\Actions\Order;

use RuntimeException;

/**
 * Raised when a rating cannot be recorded: the order has already been rated,
 * or no completed delivery exists to rate the driver for (FR-013).
 */
class RatingConflictException extends RuntimeException {}
