<?php

declare(strict_types=1);

namespace App\Livewire\Settings\TwoFactor;

use Exception;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Locked;
use Illuminate\Contracts\Auth\Guard;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

final class RecoveryCodes extends Component
{
    /** @var array<int, string> */
    #[Locked]
    public array $recoveryCodes = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadRecoveryCodes();
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        /** @var Guard $auth */
        $auth = auth();

        $generateNewRecoveryCodes($auth->user());

        $this->loadRecoveryCodes();
    }

    /**
     * Load the recovery codes for the user.
     */
    private function loadRecoveryCodes(): void
    {
        /** @var Guard $auth */
        $auth = auth();

        /** @var User $user */
        $user = $auth->user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $codes = $user->two_factor_recovery_codes;
                $decrypted = decrypt($codes);
                $decoded = json_decode(is_string($decrypted) ? $decrypted : '', true);

                /** @var array<int, string> $recoveryCodes */
                $recoveryCodes = is_array($decoded) ? $decoded : [];
                $this->recoveryCodes = $recoveryCodes;
            } catch (Exception) {
                $this->addError('recoveryCodes', 'Failed to load recovery codes');

                $this->recoveryCodes = [];
            }
        }
    }
}
