<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use Illuminate\Contracts\View\View;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Support\Facades\RateLimiter;

final class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (): Factory|View => view('livewire.auth.login'));
        Fortify::verifyEmailView(fn (): Factory|View => view('livewire.auth.verify-email'));
        Fortify::twoFactorChallengeView(fn (): Factory|View => view('livewire.auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn (): Factory|View => view('livewire.auth.confirm-password'));
        Fortify::registerView(fn (): Factory|View => view('livewire.auth.register'));
        Fortify::resetPasswordView(fn (): Factory|View => view('livewire.auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn (): Factory|View => view('livewire.auth.forgot-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));

        RateLimiter::for('login', function (Request $request) {
            $email = $request->input(Fortify::username());
            $emailString = is_string($email) ? $email : '';
            $throttleKey = Str::transliterate(Str::lower($emailString).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
