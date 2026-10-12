<?php

namespace App\Actions\Auth;

use RuntimeException;

/**
 * Thrown when a Google/Apple identity token cannot be exchanged for a
 * customer account: invalid/expired token, or the provider returned no
 * verified email (FR-001). The API surface maps this to HTTP 422.
 */
class SocialAuthFailedException extends RuntimeException {}
