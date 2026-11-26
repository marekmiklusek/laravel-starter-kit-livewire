@assets
    @vite('resources/js/passkeys.js')
@endassets

<div
    x-data="{
        supported: false,
        showForm: false,
        name: '',
        loading: false,
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },
        defaultPasskeyName() {
            const userAgent = navigator.userAgent;

            const browser = [
                { pattern: /Edg|Edge/, name: 'Edge' },
                { pattern: /OPR|Opera|OPiOS/, name: 'Opera' },
                { pattern: /Firefox|FxiOS/, name: 'Firefox' },
                { pattern: /Chrome|CriOS/, name: 'Chrome' },
                { pattern: /Safari/, name: 'Safari' },
            ].find(({ pattern }) => pattern.test(userAgent))?.name;

            const operatingSystem = [
                { pattern: /iPhone/, name: 'iPhone' },
                { pattern: /iPad|Macintosh(?=.*Mobile)/, name: 'iPad' },
                { pattern: /Android/, name: 'Android' },
                { pattern: /Mac/, name: 'Mac' },
                { pattern: /Windows/, name: 'Windows' },
            ].find(({ pattern }) => pattern.test(userAgent))?.name;

            return [browser, operatingSystem].filter(Boolean).join(' on ');
        },
        init() {
            this.name = this.defaultPasskeyName();
            this.updateSupport();

            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async register() {
            if (! this.name.trim()) {
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                await window.Passkeys.register({ name: this.name });
                this.name = this.defaultPasskeyName();
                this.showForm = false;
                await $wire.$refresh();
            } catch (error) {
                if (error.name !== 'UserCancelledError') {
                    this.error = error.message;
                }
            } finally {
                this.loading = false;
            }
        },
        cancel() {
            this.showForm = false;
            this.name = this.defaultPasskeyName();
            this.error = null;
        },
    }"
>
    <template x-if="! supported">
        <flux:text>{{ __('Passkeys are not supported in this browser.') }}</flux:text>
    </template>

    <template x-if="supported && ! showForm">
        <div>
            <flux:button variant="primary" icon="plus" x-on:click="showForm = true">
                {{ __('Add passkey') }}
            </flux:button>
        </div>
    </template>

    <template x-if="supported && showForm">
        <div class="space-y-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
            <flux:input
                :label="__('Passkey name')"
                x-model="name"
                :placeholder="__('e.g., MacBook Pro, iPhone')"
                maxlength="255"
                x-on:keydown.enter.prevent="register()"
                x-ref="passkeyNameInput"
                x-init="$nextTick(() => $refs.passkeyNameInput?.focus())"
            />
            <flux:text class="!mt-1">{{ __('Give this passkey a name to help you identify it later.') }}</flux:text>

            <p x-show="error" x-text="error" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>

            <div class="flex gap-2">
                <flux:button variant="primary" x-on:click="register()" x-bind:disabled="loading || ! name.trim()">
                    <span x-show="! loading">{{ __('Register passkey') }}</span>
                    <span x-show="loading" x-cloak>{{ __('Registering...') }}</span>
                </flux:button>
                <flux:button variant="ghost" x-on:click="cancel()"> {{ __('Cancel') }} </flux:button>
            </div>
        </div>
    </template>
</div>
