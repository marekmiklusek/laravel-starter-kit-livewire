<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Str;
use Database\Factories\UserFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable([
    'name',
    'email',
    'password',
])]
#[Hidden([
    'password',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'remember_token',
])]
final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn (string $word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
