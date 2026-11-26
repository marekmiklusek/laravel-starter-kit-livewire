<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Livewire\Livewire;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Features;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Support\Facades\RateLimiter;
use App\Actions\Fortify\ConfirmPasswordOnLogin;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use App\Http\Middleware\EnsureSecondFactorIsEnrolled;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Contracts\RedirectsIfTwoFactorAuthenticatable;

final class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureActions();
        $this->configureLoginPipeline();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureLivewireMiddleware();
    }

    private function configureLivewireMiddleware(): void
    {
        Livewire::addPersistentMiddleware([
            EnsureSecondFactorIsEnrolled::class,
        ]);
    }

    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    }

    private function configureLoginPipeline(): void
    {
        Fortify::authenticateThrough(fn (): array => array_filter([
            config()->get('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
            config()->boolean('fortify.lowercase_usernames') ? CanonicalizeUsername::class : null,
            Features::enabled(Features::twoFactorAuthentication()) ? RedirectsIfTwoFactorAuthenticatable::class : null,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
            ConfirmPasswordOnLogin::class,
        ]));
    }

    private function configureViews(): void
    {
        Fortify::loginView(fn (): Factory|View => view('livewire.auth.login'));
        Fortify::verifyEmailView(fn (): Factory|View => view('livewire.auth.verify-email'));
        Fortify::twoFactorChallengeView(fn (): Factory|View => view('livewire.auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn (Request $request): Factory|View => view('livewire.auth.confirm-password', [
            'canConfirmWithPasskey' => $this->canConfirmWithPasskey($request),
        ]));
        Fortify::resetPasswordView(fn (): Factory|View => view('livewire.auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn (): Factory|View => view('livewire.auth.forgot-password'));
    }

    private function canConfirmWithPasskey(Request $request): bool
    {
        $user = $request->user();

        return Features::canManagePasskeys() && $user instanceof User && $user->hasPasskeysEnabled();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));

        RateLimiter::for('login', function (Request $request) {
            $email = $request->input(Fortify::username());
            $emailString = is_string($email) ? $email : '';
            $throttleKey = Str::transliterate(Str::lower($emailString).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');
            $throttleIdentifier = is_string($credentialId) && $credentialId !== '' ? $credentialId : $request->session()->getId();

            return Limit::perMinute(10)->by($throttleIdentifier.'|'.$request->ip());
        });
    }
}
