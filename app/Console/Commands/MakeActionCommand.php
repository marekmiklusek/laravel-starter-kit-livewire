<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Override;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;

#[Signature('make:action {name : The name of the action}')]
#[Description('Create a new action class')]
final class MakeActionCommand extends GeneratorCommand
{
    #[Override]
    protected $type = 'Action';

    public function getStub(): string
    {
        return $this->laravel->basePath('stubs/action.stub');
    }

    public function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Actions';
    }
}
