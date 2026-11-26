<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Actions\MarkSecondFactorEnrolled;
use Laravel\Passkeys\Events\PasskeyRegistered;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;

final readonly class RecordSecondFactorEnrollment
{
    public function __construct(private MarkSecondFactorEnrolled $markSecondFactorEnrolled) {}

    public function handle(TwoFactorAuthenticationEnabled|TwoFactorAuthenticationConfirmed|PasskeyRegistered $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        if (! $event instanceof PasskeyRegistered && ! $user->hasEnabledTwoFactorAuthentication()) {
            return;
        }

        $this->markSecondFactorEnrolled->execute($user);
    }
}
