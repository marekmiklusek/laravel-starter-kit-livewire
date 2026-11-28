<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist>
            <flux:navlist.item :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item :href="route('user-password.edit')" wire:navigate>
                {{ __('Password') }}</flux:navlist.item>
            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <flux:navlist.item :href="route('two-factor.show')" wire:navigate>
                    {{ __('Two-Factor Auth') }}</flux:navlist.item>
            @endif
            @if (Laravel\Fortify\Features::canManagePasskeys())
                <flux:navlist.item :href="route('passkeys.show')" wire:navigate>{{ __('Passkeys') }}</flux:navlist.item>
            @endif
            <flux:navlist.item :href="route('appearance.edit')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        @if (config()->boolean('fortify.second_factor_required') && auth()->user()?->second_factor_enrolled_at === null)
            <flux:callout
                variant="warning"
                icon="shield-exclamation"
                class="mb-6 max-w-lg"
                data-test="second-factor-enrollment-notice"
            >
                <flux:callout.heading>{{ __('Secure your account') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Set up two-factor authentication or add a passkey to continue.') }}
                </flux:callout.text>
            </flux:callout>
        @endif

        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-lg">{{ $slot }}</div>
    </div>
</div>
