<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\User;
use Laravel\Passkeys\Passkey;
use Illuminate\Database\Eloquent\Collection;

final readonly class UserPasskeysQuery
{
    /**
     * @return Collection<int, Passkey>
     */
    public function get(User $user): Collection
    {
        return $user->passkeys()
            ->latest()
            ->get();
    }
}
