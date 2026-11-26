<?php

declare(strict_types=1);

use App\Models\User;
use App\Actions\MarkSecondFactorEnrolled;

it('marks the user as enrolled', function (): void {
    $user = User::factory()->withoutSecondFactorEnrollment()->create();

    resolve(MarkSecondFactorEnrolled::class)->execute($user);

    expect($user->refresh()->second_factor_enrolled_at?->toDateTimeString())->toBe(now()->toDateTimeString());
});

it('keeps the original enrollment time', function (): void {
    $enrolledAt = now()->subWeek();

    $user = User::factory()->create(['second_factor_enrolled_at' => $enrolledAt]);

    resolve(MarkSecondFactorEnrolled::class)->execute($user);

    expect($user->refresh()->second_factor_enrolled_at?->toDateTimeString())->toBe($enrolledAt->toDateTimeString());
});
