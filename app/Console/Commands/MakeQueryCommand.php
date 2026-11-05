<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Override;
use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;

#[Signature('make:query {name : The name of the query}')]
#[Description('Create a new query class')]
final class MakeQueryCommand extends GeneratorCommand
{
    #[Override]
    protected $type = 'Query';

    public function getStub(): string
    {
        return $this->laravel->basePath('stubs/query.stub');
    }

    public function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Queries';
    }

    public function getNameInput(): string
    {
        return Str::finish(parent::getNameInput(), 'Query');
    }
}
