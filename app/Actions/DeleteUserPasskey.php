<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Actions\DeletePasskey;

final readonly class DeleteUserPasskey
{
    public function __construct(private DeletePasskey $deletePasskey) {}

    public function execute(User $user, int $passkeyId): void
    {
        /** @var Passkey $passkey */
        $passkey = $user->passkeys()->whereKey($passkeyId)->firstOrFail();

        ($this->deletePasskey)($user, $passkey);
    }
}
