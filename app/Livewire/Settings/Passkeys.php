<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\User;
use Livewire\Component;
use Laravel\Fortify\Features;
use Laravel\Passkeys\Passkey;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Computed;
use App\Actions\DeleteUserPasskey;
use App\Queries\UserPasskeysQuery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\Response;

final class Passkeys extends Component
{
    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingPasskeyId = null;

    #[Locked]
    public string $deletingPasskeyName = '';

    public function mount(): void
    {
        abort_unless(Features::canManagePasskeys(), Response::HTTP_FORBIDDEN);
    }

    /**
     * @return Collection<int, Passkey>
     */
    #[Computed]
    public function passkeys(): Collection
    {
        /** @var User $user */
        $user = Auth::user();

        return resolve(UserPasskeysQuery::class)->get($user);
    }

    public function confirmDelete(int $passkeyId): void
    {
        $passkey = $this->passkeys()->firstOrFail('id', $passkeyId);

        $this->deletingPasskeyId = $passkey->id;
        $this->deletingPasskeyName = $passkey->name;
        $this->showDeleteModal = true;
    }

    public function deletePasskey(DeleteUserPasskey $deleteUserPasskey): void
    {
        if ($this->deletingPasskeyId === null) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        $deleteUserPasskey->execute($user, $this->deletingPasskeyId);

        $this->closeDeleteModal();

        unset($this->passkeys);
    }

    public function closeDeleteModal(): void
    {
        $this->reset('showDeleteModal', 'deletingPasskeyId', 'deletingPasskeyName');
    }
}
