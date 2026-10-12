<?php

namespace App\Actions\Order;

use RuntimeException;

class IdempotencyKeyConflictException extends RuntimeException {}
