<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Features;
use Symfony\Component\HttpFoundation\Response;
use App\Queries\RequiresSecondFactorEnrollmentQuery;

final readonly class EnsureSecondFactorIsEnrolled
{
    public function __construct(private RequiresSecondFactorEnrollmentQuery $requiresSecondFactorEnrollmentQuery) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $request->routeIs('two-factor.*', 'passkey.*', 'passkeys.show', 'password.confirm*', 'verification.*', 'logout', '*livewire.*')) {
            return $next($request);
        }

        if (! $this->requiresSecondFactorEnrollmentQuery->get($user)) {
            return $next($request);
        }

        return to_route(Features::canManageTwoFactorAuthentication() ? 'two-factor.show' : 'passkeys.show');
    }
}
