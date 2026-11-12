<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final readonly class DeleteUser
{
    public function execute(User $user): void
    {
        $user->delete();
    }
}
