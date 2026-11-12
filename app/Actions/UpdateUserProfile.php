<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final readonly class UpdateUserProfile
{
    public function execute(User $user, string $name, string $email): void
    {
        $user->fill([
            'name' => $name,
            'email' => $email,
        ]);

        if ($user->isDirty('email')) {
            $user->forceFill(['email_verified_at' => null]);
        }

        $user->save();
    }
}
