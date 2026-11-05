<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Override;
use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;

#[Signature('make:dto {name : The name of the dto}')]
#[Description('Create a new dto class')]
final class MakeDtoCommand extends GeneratorCommand
{
    #[Override]
    protected $type = 'Dto';

    public function getStub(): string
    {
        return $this->laravel->basePath('stubs/dto.stub');
    }

    public function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Dtos';
    }

    public function getNameInput(): string
    {
        return Str::finish(parent::getNameInput(), 'Dto');
    }
}
