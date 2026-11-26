<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Passkeys\Passkey;
use App\Actions\DeleteUserPasskey;
use Illuminate\Support\Facades\Event;
use Laravel\Passkeys\Events\PasskeyDeleted;
use Illuminate\Database\Eloquent\ModelNotFoundException;

it('deletes the passkey of the user', function (): void {
    Event::fake([PasskeyDeleted::class]);

    $user = User::factory()->create();

    /** @var Passkey $passkey */
    $passkey = $user->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => 'credential-id',
        'credential' => [],
    ]);

    resolve(DeleteUserPasskey::class)->execute($user, $passkey->id);

    $this->assertModelMissing($passkey);

    Event::assertDispatched(PasskeyDeleted::class);
});

it('does not delete a passkey of another user', function (): void {
    $user = User::factory()->create();

    /** @var Passkey $passkey */
    $passkey = User::factory()->create()->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => 'credential-id',
        'credential' => [],
    ]);

    expect(fn () => resolve(DeleteUserPasskey::class)->execute($user, $passkey->id))
        ->toThrow(ModelNotFoundException::class);

    $this->assertModelExists($passkey);
});
