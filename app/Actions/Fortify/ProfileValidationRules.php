<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

trait ProfileValidationRules
{
    /**
     * @return array<int, string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'min:1', 'max:255'];
    }

    /**
     * @return array<int, Unique|string>
     */
    protected function emailRules(): array
    {
        return [
            'required',
            'string',
            'email',
            'min:3',
            'max:255',
            Rule::unique(User::class),
        ];
    }
}
