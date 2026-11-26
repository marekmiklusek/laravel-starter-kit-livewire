@props([
    'optionsRoute' => 'passkey.login-options',
    'submitRoute' => 'passkey.login',
    'label' => __('Sign in with a passkey'),
    'loadingLabel' => __('Authenticating...'),
    'separator' => __('Or continue with email'),
])

@vite('resources/js/passkeys.js')

<div
    x-data="{
        supported: false,
        loading: false,
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported())
        },
        init() {
            this.updateSupport()

            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true })
        },
        async verify() {
            this.loading = true
            this.error = null

            try {
                const response = await window.Passkeys.verify({
                    routes: {
                        options: @js(route($optionsRoute)),
                        submit: @js(route($submitRoute)),
                    },
                })

                window.location.href = response.redirect || @js(route('dashboard'))
            } catch (error) {
                if (error.name !== 'UserCancelledError') {
                    this.error = error.message
                }
            } finally {
                this.loading = false
            }
        },
    }"
>
    <template x-if="supported">
        <div>
            <div class="grid gap-2">
                <flux:button
                    variant="outline"
                    icon="finger-print"
                    class="w-full"
                    x-on:click="verify()"
                    x-bind:disabled="loading"
                >
                    <span x-show="! loading">{{ $label }}</span>
                    <span x-show="loading" x-cloak>{{ $loadingLabel }}</span>
                </flux:button>
                <p x-show="error" x-text="error" x-cloak class="text-center text-sm text-red-600 dark:text-red-400"></p>
            </div>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-zinc-200 dark:border-zinc-700"></div>
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-white px-2 text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                        {{ $separator }}
                    </span>
                </div>
            </div>
        </div>
    </template>
</div>
