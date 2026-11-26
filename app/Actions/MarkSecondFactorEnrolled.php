<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final readonly class MarkSecondFactorEnrolled
{
    public function execute(User $user): void
    {
        if ($user->second_factor_enrolled_at !== null) {
            return;
        }

        $user->forceFill(['second_factor_enrolled_at' => now()])->save();
    }
}
