<div class="grow">
    <form wire:submit="updateProfileInformation">
        <!-- Panel body -->
        <div class="p-6 space-y-6" x-data="{ photoName: null, photoPreview: null }">
            <h2 class="text-2xl text-slate-800 font-bold mb-5">Mi Cuenta</h2>

            <!-- Picture -->
            <section>
                <h3 class="text-xl leading-snug text-slate-800 font-bold mb-1">Foto</h3>
                <div class="flex items-center">

                    <div class="mr-4" x-show="! photoPreview">
                        <!-- Current Profile Photo -->

                        <img class="w-20 h-20 rounded-full" src="{{ $this->user->profile_photo_url }}"
                            alt="{{ $this->user->name }}" width="80" height="80" />
                    </div>
                    <!-- New Profile Photo Preview -->
                    <div class="mr-4" x-show="photoPreview" style="display: none;">
                        <span class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center"
                            x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
                        </span>
                    </div>
                    <input type="file" class="hidden" wire:model.live="photo" x-ref="photo"
                        x-on:change="
                                                                            photoName = $refs.photo.files[0].name;
                                                                            const reader = new FileReader();
                                                                            reader.onload = (e) => {
                                                                                photoPreview = e.target.result;
                                                                            };
                                                                            reader.readAsDataURL($refs.photo.files[0]);
                                                                    " />


                    <button x-on:click.prevent="$refs.photo.click()"
                        class="btn-sm mr-2 bg-indigo-500 hover:bg-indigo-600 text-white">Cambiar</button>
                    @if ($this->user->profile_photo_path)
                        <button wire:click="deleteProfilePhoto" type="button"
                            class="mr-2 btn-sm bg-red-500 hover:bg-red-600
                    text-white">Eliminar
                            Foto</button>
                    @endif
                    <x-input-error for="photo" class="mt-2" />
                </div>
            </section>

            <!-- Business Profile -->
            <section>
                <h3 class="text-xl leading-snug text-slate-800 font-bold mb-1">Perfil</h3>
                <div class="text-sm">Actualice la información de su cuenta y la dirección de correo electrónico.</div>
                <div class="sm:flex sm:items-center space-y-4 sm:space-y-0 sm:space-x-4 mt-5">
                    <div class="sm:w-1/3">
                        <label class="block text-sm font-medium mb-1" for="name">Nombre:</label>
                        <input id="name" class="form-input w-full" type="text" wire:model.live="state.name"
                            autocomplete="name" />
                        <x-input-error for="name" class="mt-2" />
                    </div>
                    {{-- <div class="sm:w-1/3">
                        <label class="block text-sm font-medium mb-1" for="business-id">Business
                            ID</label>
                        <input id="business-id" class="form-input w-full" type="text" value="Kz4tSEqtUmA" />
                    </div>
                    <div class="sm:w-1/3">
                        <label class="block text-sm font-medium mb-1" for="location">Location</label>
                        <input id="location" class="form-input w-full" type="text" value="London, UK" />
                    </div> --}}
                </div>
            </section>

            <!-- Email -->
            <section>
                <h3 class="text-xl leading-snug text-slate-800 font-bold mb-1">Email</h3>
                <div class="text-sm">Asegúrese que su cuenta esté usando un email unico.</div>
                <div class="flex flex-wrap mt-5">
                    <div class="mr-2">
                        <label class="sr-only" for="email">Business email</label>
                        <input id="email" class="form-input" type="email" wire:model.live="state.email" />
                        <x-input-error for="email" class="mt-2" />
                    </div>
                    {{-- <button
                        class="btn border-slate-200 hover:border-slate-300 shadow-sm text-indigo-500">Change</button>
                    --}}
                </div>
            </section>

            <!-- Password -->
            <section>
                <h3 class="text-xl leading-snug text-slate-800 font-bold mb-1">Contraseña</h3>
                <div class="text-sm">Asegúrese que su cuenta esté usando una contraseña larga y aleatoria para
                    mantenerse seguro.</div>
                <div class="mt-5">
                    <button wire:click.prevent="openModalPassword"
                        class="btn border-slate-200 shadow-sm text-indigo-500">Establecer nueva Contraseña</button>
                </div>
            </section>



            {{-- <section>
                <h3 class="text-xl leading-snug text-slate-800 font-bold mb-1">Smart Sync update for Mac
                </h3>
                <div class="flex items-center mt-5" x-data="{ checked: true }">
                    <div class="form-switch">
                        <input type="checkbox" id="toggle" class="sr-only" x-model="checked" />
                        <label class="bg-slate-400" for="toggle">
                            <span class="bg-white shadow-sm" aria-hidden="true"></span>
                            <span class="sr-only">Enable smart sync</span>
                        </label>
                    </div>
                    <div class="text-sm text-slate-400 italic ml-2" x-text="checked ? 'On' : 'Off'">
                    </div>
                </div>
            </section> --}}
        </div>

        <!-- Panel footer -->
        <footer>
            <div class="flex flex-col px-6 py-5 border-t border-slate-200">
                <div class="flex self-end">
                    {{-- <button type="button"
                        class="btn border-slate-200 hover:border-slate-300 text-slate-600">Cancelar</button> --}}
                    <x-action-message class="mr-3" on="saved">
                        {{ __('Saved.') }}
                        </x-jet-action-message>
                        <button type="submit" wire:loading.attr="disabled" wire:target="photo"
                            class="btn bg-indigo-500 disabled:bg-indigo-300 hover:bg-indigo-600 text-white ml-3">Guardar
                            Cambios</button>

                </div>
            </div>
        </footer>
    </form>
    @livewire('ajustes.cuenta.update-password')
    <x-section-border />

    <div class="mx-2">
        @livewire('ajustes.cuenta.two-factor-authentication')

    </div>
    <x-section-border />
    <div class="mx-2">
        @livewire('ajustes.cuenta.logout-other-browser')

    </div>
    <x-section-border />
</div>
