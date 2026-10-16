<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

it('places beforeEach and afterEach before helper functions and tests', function (): void {
    $violations = collect(File::allFiles(base_path('tests')))
        ->filter(fn (SplFileInfo $file): bool => $file->getExtension() === 'php')
        ->flatMap(function (SplFileInfo $file): array {
            $tokens = array_values(array_filter(
                PhpToken::tokenize($file->getContents()),
                fn (PhpToken $token): bool => ! $token->isIgnorable(),
            ));

            $depth = 0;
            $bodyStarted = false;
            $violations = [];

            foreach ($tokens as $index => $token) {
                if ($token->is(['{', '(', '[', T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES, T_ATTRIBUTE])) {
                    $depth++;

                    continue;
                }

                if ($token->is(['}', ')', ']'])) {
                    $depth--;

                    continue;
                }

                if ($depth > 0) {
                    continue;
                }

                $previous = $tokens[$index - 1] ?? null;
                $next = $tokens[$index + 1] ?? null;

                if ($token->is(T_FUNCTION) && $next?->is(T_STRING)) {
                    $bodyStarted = true;

                    continue;
                }

                $isFunctionCall = $token->is(T_STRING)
                    && $next?->is('(')
                    && ! $previous?->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW, T_FUNCTION]);

                if (! $isFunctionCall) {
                    continue;
                }

                if (in_array($token->text, ['beforeEach', 'afterEach'], true) && $bodyStarted) {
                    $violations[] = $file->getRelativePathname().':'.$token->line;
                }

                if (in_array($token->text, ['it', 'test', 'arch'], true)) {
                    $bodyStarted = true;
                }
            }

            return $violations;
        })
        ->values()
        ->all();

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});
