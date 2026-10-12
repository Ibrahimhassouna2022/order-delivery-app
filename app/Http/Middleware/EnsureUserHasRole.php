<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $requiredRole = UserRole::tryFrom($role);

        if ($requiredRole === null || $request->user()?->role !== $requiredRole) {
            abort(403);
        }

        return $next($request);
    }
}
