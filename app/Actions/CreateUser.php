<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Actions\Fortify\ProfileValidationRules;
use App\Actions\Fortify\PasswordValidationRules;

final readonly class CreateUser
{
    use PasswordValidationRules;
    use ProfileValidationRules;

    /**
     * @param  array<string, string>  $input
     */
    public function execute(array $input): User
    {
        Validator::make($input, [
            'name' => $this->nameRules(),
            'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return User::query()->forceCreate([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'email_verified_at' => now(),
        ]);
    }
}
