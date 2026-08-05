<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use RuntimeException;
use Laravel\Fortify\Features;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws RuntimeException When self-registration is disabled.
     */
    public function create(array $input): User
    {
        throw_unless(Features::enabled(Features::registration()), RuntimeException::class, 'Registration is disabled.');

        Validator::make($input, [
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'min:3',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return User::query()->create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
    }
}
