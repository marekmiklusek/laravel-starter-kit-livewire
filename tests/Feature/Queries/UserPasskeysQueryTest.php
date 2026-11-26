<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Passkeys\Passkey;
use App\Queries\UserPasskeysQuery;

it('returns the passkeys of the user from the newest', function (): void {
    $user = User::factory()->create();

    /** @var Passkey $olderPasskey */
    $olderPasskey = $user->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => 'older-credential-id',
        'credential' => [],
    ]);

    $this->travel(1)->minute();

    /** @var Passkey $newerPasskey */
    $newerPasskey = $user->passkeys()->create([
        'name' => 'iPhone',
        'credential_id' => 'newer-credential-id',
        'credential' => [],
    ]);

    User::factory()->create()->passkeys()->create([
        'name' => 'Foreign',
        'credential_id' => 'foreign-credential-id',
        'credential' => [],
    ]);

    $passkeys = resolve(UserPasskeysQuery::class)->get($user);

    expect($passkeys->modelKeys())->toBe([$newerPasskey->id, $olderPasskey->id]);
});
