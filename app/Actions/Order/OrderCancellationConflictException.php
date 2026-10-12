<?php

namespace App\Actions\Order;

use RuntimeException;

/**
 * Raised when an order cannot be cancelled under the current workflow state
 * (already delivered/cancelled, or an active driver assignment for a
 * customer-initiated cancellation) — FR-041.
 */
class OrderCancellationConflictException extends RuntimeException {}
