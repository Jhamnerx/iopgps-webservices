<x-action-section>
    <x-slot name="title">
        {{ __('Two Factor Authentication') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Add additional security to your account using two factor authentication.') }}
    </x-slot>

    <x-slot name="content">
        <h3 class="text-lg font-medium text-gray-900">
            @if ($this->enabled)
                {{ __('You have enabled two factor authentication.') }}
            @else
                {{ __('You have not enabled two factor authentication.') }}
            @endif
        </h3>

        <div class="mt-3 max-w-xl text-sm text-gray-600">
            <p>
                Cuando la autenticación de dos factores esté habilitada, le pediremos un token aleatorio seguro durante
                la
                autenticación. Puede recuperar este token desde la aplicación Google Authenticator de su teléfono.
            </p>
        </div>

        @if ($this->enabled)
            @if ($showingQrCode)
                <div class="mt-4 max-w-xl text-sm text-gray-600">
                    <p class="font-semibold">
                        La autenticación de dos factores ahora está habilitada. Escanee el siguiente código QR usando la
                        aplicación de
                        autenticación de su teléfono.
                    </p>
                </div>

                <div class="mt-4">
                    {!! $this->user->twoFactorQrCodeSvg() !!}
                </div>
            @endif

            @if ($showingRecoveryCodes)
                <div class="mt-4 max-w-xl text-sm text-gray-600">
                    <p class="font-semibold">
                        Guarde estos códigos de recuperación en un administrador de contraseñas seguro. Se pueden
                        utilizar para
                        recuperar el
                        acceso a su cuenta si pierde su dispositivo de autenticación de dos factores.
                    </p>
                </div>

                <div class="grid gap-1 max-w-xl mt-4 px-4 py-4 font-mono text-sm bg-gray-100 rounded-lg">
                    @foreach (json_decode(decrypt($this->user->two_factor_recovery_codes), true) as $code)
                        <div>{{ $code }}</div>
                    @endforeach
                </div>
            @endif
        @endif

        <div class="mt-5">
            @if (!$this->enabled)
                <x-confirms-password wire:then="enableTwoFactorAuthentication">
                    <x-button type="button" wire:loading.attr="disabled">
                        {{ __('Enable') }}
                    </x-button>
                    </x-jet-confirms-password>
                @else
                    @if ($showingRecoveryCodes)
                        <x-confirms-password wire:then="regenerateRecoveryCodes">
                            <x-secondary-button class="mr-3">
                                {{ __('Regenerate Recovery Codes') }}
                                </x-jet-secondary-button>
                                </x-jet-confirms-password>
                            @else
                                <x-confirms-password wire:then="showRecoveryCodes">
                                    <x-secondary-button class="mr-3">
                                        {{ __('Show Recovery Codes') }}
                                        </x-jet-secondary-button>
                                        </x-jet-confirms-password>
                    @endif

                    <x-confirms-password wire:then="disableTwoFactorAuthentication">
                        <x-danger-button wire:loading.attr="disabled">
                            {{ __('Disable') }}
                            </x-jet-danger-button>
                            </x-jet-confirms-password>
            @endif
        </div>
    </x-slot>
    </x-jet-action-section>
