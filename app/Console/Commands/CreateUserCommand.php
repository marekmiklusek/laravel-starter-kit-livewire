<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\CreateUser;
use Illuminate\Console\Command;

use function Laravel\Prompts\text;
use function Laravel\Prompts\password;

use Illuminate\Support\Facades\Validator;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;
use App\Actions\Fortify\ProfileValidationRules;
use App\Actions\Fortify\PasswordValidationRules;

#[Signature('user:create')]
#[Description('Create a new user')]
final class CreateUserCommand extends Command
{
    use PasswordValidationRules;
    use ProfileValidationRules;

    public function handle(CreateUser $createUser): int
    {
        $name = text(label: 'Name', required: true, validate: ['name' => $this->nameRules()]);
        $email = text(label: 'Email', required: true, validate: ['email' => $this->emailRules()]);
        $password = password(label: 'Password', required: true, validate: ['password' => $this->passwordRulesWithoutConfirmation()]);

        $passwordConfirmation = password(
            label: 'Confirm password',
            required: true,
            validate: fn (string $value): string => Validator::make(
                ['password' => $password, 'password_confirmation' => $value],
                ['password' => ['confirmed']],
            )->errors()->first('password'),
        );

        $user = $createUser->execute([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ]);

        $this->components->success(sprintf('User [%s] created successfully.', $user->email));

        return self::SUCCESS;
    }
}
