<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\User;
use Laravel\Fortify\Features;

final readonly class RequiresSecondFactorEnrollmentQuery
{
    public function get(User $user): bool
    {
        if (! config()->boolean('fortify.second_factor_required')) {
            return false;
        }

        $twoFactorAvailable = Features::canManageTwoFactorAuthentication();
        $passkeysAvailable = Features::canManagePasskeys();

        if (! $twoFactorAvailable && ! $passkeysAvailable) {
            return false;
        }

        if ($twoFactorAvailable && $user->hasEnabledTwoFactorAuthentication()) {
            return false;
        }

        return ! $passkeysAvailable || ! $user->passkeys()->exists();
    }
}
