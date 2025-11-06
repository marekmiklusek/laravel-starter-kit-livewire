<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\User;
use Livewire\Component;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;

final class DeleteUserForm extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        tap($user, $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}
