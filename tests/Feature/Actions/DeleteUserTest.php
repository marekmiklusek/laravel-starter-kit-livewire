<?php

declare(strict_types=1);

use App\Models\User;
use App\Actions\DeleteUser;

it('deletes the user', function (): void {
    $user = User::factory()->create();

    resolve(DeleteUser::class)->execute($user);

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});
