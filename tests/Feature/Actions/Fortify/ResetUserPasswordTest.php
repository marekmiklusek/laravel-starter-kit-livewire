<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Validation\ValidationException;

it('resets the user password', function (): void {
    $user = User::factory()->create();

    new ResetUserPassword()->reset($user, [
        'password' => 'NewPassword123',
        'password_confirmation' => 'NewPassword123',
    ]);

    expect(Hash::check('NewPassword123', $user->refresh()->password))->toBeTrue();
});

it('validates the password when resetting', function (): void {
    $user = User::factory()->create();

    expect(fn () => new ResetUserPassword()->reset($user, [
        'password' => 'short',
        'password_confirmation' => 'mismatch',
    ]))->toThrow(ValidationException::class);
});
