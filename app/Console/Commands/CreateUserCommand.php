<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\CreateUser;
use Illuminate\Support\Str;
use Illuminate\Console\Command;

use function Laravel\Prompts\text;

use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;
use App\Actions\Fortify\ProfileValidationRules;

#[Signature('user:create')]
#[Description('Create a new user')]
final class CreateUserCommand extends Command
{
    use ProfileValidationRules;

    public function handle(CreateUser $createUser): int
    {
        $name = text(label: 'Name', required: true, validate: ['name' => $this->nameRules()]);
        $email = text(label: 'Email', required: true, validate: ['email' => $this->emailRules()]);

        $password = Str::random(32);

        $user = $createUser->execute($name, $email, $password);

        $this->components->success(sprintf('User [%s] created successfully.', $user->email));
        $this->components->twoColumnDetail('Password', $password);

        return self::SUCCESS;
    }
}
