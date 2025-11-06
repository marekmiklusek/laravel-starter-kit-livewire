<?php

declare(strict_types=1);

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

final class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke(): never
    {
        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();

        redirect('/')->send();

        exit;
    }
}
