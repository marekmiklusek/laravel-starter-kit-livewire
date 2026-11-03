<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final readonly class CreateUser
{
    public function execute(string $name, string $email, string $password): User
    {
        return User::query()->forceCreate([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
        ]);
    }
}
