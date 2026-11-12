<?php

declare(strict_types=1);

use App\Models\User;
use App\Actions\UpdateUserPassword;
use Illuminate\Support\Facades\Hash;

it('updates the password as a hash', function (): void {
    $user = User::factory()->create();

    resolve(UpdateUserPassword::class)->execute($user, 'NewPassword123');

    expect(Hash::check('NewPassword123', $user->refresh()->password))->toBeTrue();
});
