<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final readonly class UpdateUserPassword
{
    public function execute(User $user, string $password): void
    {
        $user->update([
            'password' => $password,
        ]);
    }
}
