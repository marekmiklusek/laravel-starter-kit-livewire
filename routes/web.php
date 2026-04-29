<?php

declare(strict_types=1);

use Laravel\Fortify\Features;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\TwoFactor;
use App\Livewire\Settings\Appearance;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'login')->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function (): void {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    if (Features::canManageTwoFactorAuthentication()) {
        Route::get('settings/two-factor', TwoFactor::class)
            ->when(
                Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                fn ($route) => $route->middleware(['password.confirm']),
            )
            ->name('two-factor.show');
    }
});
