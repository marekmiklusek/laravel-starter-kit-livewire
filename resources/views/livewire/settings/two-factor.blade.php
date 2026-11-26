<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('Two Factor Authentication')"
        :subheading="__('Manage your two-factor authentication settings')"
    >
        <div class="mx-auto flex w-full flex-col space-y-6 text-sm" wire:cloak>
            @if ($showSetup)
                <div
                    class="space-y-6 rounded-xl border border-stone-200 p-6 dark:border-stone-700"
                    data-test="two-factor-setup"
                >
                    <div class="space-y-2">
                        <flux:heading size="lg">{{ $this->setupConfig['title'] }}</flux:heading>
                        <flux:text>{{ $this->setupConfig['description'] }}</flux:text>
                    </div>

                    @if ($showVerificationStep)
                        <div class="space-y-6">
                            <div class="flex flex-col items-center space-y-3">
                                <x-input-otp :digits="6" name="code" wire:model="code" autocomplete="one-time-code" />
                                @error('code')
                                    <flux:text color="red"> {{ $message }} </flux:text>
                                @enderror
                            </div>

                            <div class="flex items-center space-x-3">
                                <flux:button variant="outline" class="flex-1" wire:click="resetVerification">
                                    {{ __('Back') }}
                                </flux:button>

                                <flux:button
                                    variant="primary"
                                    class="flex-1"
                                    wire:click="confirmTwoFactor"
                                    x-bind:disabled="$wire.code.length < 6"
                                >
                                    {{ __('Confirm') }}
                                </flux:button>
                            </div>
                        </div>
                    @else
                        @error('setupData')
                            <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}" />
                        @enderror

                        <div class="flex justify-center">
                            <div class="relative aspect-square w-64 overflow-hidden rounded-lg border border-stone-200 dark:border-stone-700">
                                @empty($qrCodeSvg)
                                    <div class="absolute inset-0 flex animate-pulse items-center justify-center bg-white dark:bg-stone-700">
                                        <flux:icon.loading />
                                    </div>
                                @else
                                    <div class="flex h-full items-center justify-center p-4">
                                        <div class="rounded bg-white p-3">{!! $qrCodeSvg !!}</div>
                                    </div>
                                @endempty
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex w-full items-center gap-3 text-sm text-stone-600 dark:text-stone-400">
                                <div class="h-px flex-1 bg-stone-200 dark:bg-stone-600"></div>
                                <span>{{ __('or, enter the code manually') }}</span>
                                <div class="h-px flex-1 bg-stone-200 dark:bg-stone-600"></div>
                            </div>

                            <div
                                class="flex items-center space-x-2"
                                x-data="{
                                    copied: false,
                                    async copy() {
                                        try {
                                            await navigator.clipboard.writeText('{{ $manualSetupKey }}')
                                            this.copied = true
                                            setTimeout(() => (this.copied = false), 1500)
                                        } catch (e) {
                                            console.warn('Could not copy to clipboard')
                                        }
                                    },
                                }"
                            >
                                <div class="flex w-full items-stretch rounded-xl border dark:border-stone-700">
                                    @empty($manualSetupKey)
                                        <div class="flex w-full items-center justify-center bg-stone-100 p-3 dark:bg-stone-700">
                                            <flux:icon.loading variant="mini" />
                                        </div>
                                    @else
                                        <input
                                            type="text"
                                            readonly
                                            value="{{ $manualSetupKey }}"
                                            class="w-full bg-transparent p-3 text-stone-900 outline-none dark:text-stone-100"
                                        />

                                        <button
                                            @click="copy()"
                                            class="cursor-pointer border-l border-stone-200 px-3 transition-colors dark:border-stone-600"
                                        >
                                            <flux:icon.document-duplicate x-show="! copied" variant="outline" />
                                            <flux:icon.check x-show="copied" variant="solid" class="text-green-500" />
                                        </button>
                                    @endempty
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <flux:button variant="outline" class="flex-1" wire:click="closeSetup">
                                {{ __('Cancel') }}
                            </flux:button>

                            <flux:button
                                :disabled="$errors->has('setupData')"
                                variant="primary"
                                class="flex-1"
                                wire:click="showVerificationIfNecessary"
                            >
                                {{ $this->setupConfig['buttonText'] }}
                            </flux:button>
                        </div>
                    @endif
                </div>
            @elseif ($twoFactorEnabled)
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <flux:badge color="green">{{ __('Enabled') }}</flux:badge>
                    </div>

                    <flux:text>
                        {{ __('With two-factor authentication enabled, you will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                    </flux:text>

                    <livewire:settings.two-factor.recovery-codes :$requiresConfirmation />

                    <div class="flex justify-start">
                        <flux:button
                            variant="danger"
                            icon="shield-exclamation"
                            icon:variant="outline"
                            wire:click="disable"
                        >
                            {{ __('Disable 2FA') }}
                        </flux:button>
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <flux:badge color="red">{{ __('Disabled') }}</flux:badge>
                    </div>

                    <flux:text variant="subtle">
                        {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                    </flux:text>

                    <flux:button variant="primary" icon="shield-check" icon:variant="outline" wire:click="enable">
                        {{ __('Enable 2FA') }}
                    </flux:button>
                </div>
            @endif
        </div>
    </x-settings.layout>
</section>
