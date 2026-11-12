<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\User;
use Livewire\Component;
use App\Actions\DeleteUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

final class DeleteUserForm extends Component
{
    public string $password = '';

    public function deleteUser(DeleteUser $deleteUser): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $deleteUser->execute($user);

        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();

        $this->redirect('/', navigate: true);
    }
}
