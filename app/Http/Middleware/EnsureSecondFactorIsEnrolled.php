<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Features;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSecondFactorIsEnrolled
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->second_factor_enrolled_at !== null) {
            return $next($request);
        }

        $enrollmentRoute = $this->enrollmentRoute();

        if ($enrollmentRoute === null || ! config()->boolean('fortify.second_factor_required')) {
            return $next($request);
        }

        if ($request->routeIs('two-factor.*', 'passkey.*', 'passkeys.show', 'password.confirm*', 'verification.*', 'logout', '*livewire.*')) {
            return $next($request);
        }

        return to_route($enrollmentRoute);
    }

    private function enrollmentRoute(): ?string
    {
        if (Features::canManageTwoFactorAuthentication()) {
            return 'two-factor.show';
        }

        if (Features::canManagePasskeys()) {
            return 'passkeys.show';
        }

        return null;
    }
}
