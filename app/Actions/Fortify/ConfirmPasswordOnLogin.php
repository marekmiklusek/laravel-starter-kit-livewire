<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use Closure;
use Illuminate\Http\Request;

final class ConfirmPasswordOnLogin
{
    /**
     * @param  Closure(Request): mixed  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $request->session()->passwordConfirmed();

        return $next($request);
    }
}
