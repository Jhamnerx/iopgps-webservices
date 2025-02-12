<div>

    <div x-data="{ modalOpen: @entangle('modalOpen').live }">

        <div class="relative inline-flex">
        </div>
        <!-- Modal backdrop -->
        <div class="fixed inset-0 bg-slate-900 bg-opacity-30 z-50 transition-opacity" x-show="modalOpen"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-out duration-100"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" aria-hidden="true" x-cloak>
        </div>
        <!-- Modal dialog -->
        <div id="basic-modal"
            class="fixed inset-0 z-50 overflow-hidden flex items-center my-4 justify-center transform px-4 sm:px-6"
            role="dialog" aria-modal="true" x-show="modalOpen" x-transition:enter="transition ease-in-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in-out duration-200"
            x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
            x-cloak>

            <div class="bg-white rounded shadow-lg overflow-auto w-full md:w-3/4 lg:w-6/12 xl:w-6/12 2xl:w-1/3 max-h-full"
                @keydown.escape.window="modalOpen = false">
                <!-- Modal header -->
                <div class="px-5 py-3 border-b border-slate-200">
                    <div class="flex justify-between items-center">
                        <div class="font-semibold text-slate-800">Actualizar contraseña</div>
                        <button class="text-slate-400 hover:text-slate-500" @click="modalOpen = false">
                            <div class="sr-only">Close</div>
                            <svg class="w-4 h-4 fill-current">
                                <path
                                    d="M7.95 6.536l4.242-4.243a1 1 0 111.415 1.414L9.364 7.95l4.243 4.242a1 1 0 11-1.415 1.415L7.95 9.364l-4.243 4.243a1 1 0 01-1.414-1.415L6.536 7.95 2.293 3.707a1 1 0 011.414-1.414L7.95 6.536z" />
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- Modal content -->
                <form wire:submit="updatePassword">
                    <div class="px-8 py-5 bg-white sm:p-6">

                        <div class="grid grid-cols-12 gap-6">
                            <div class="col-span-6 sm:col-span-12">

                                <x-form.password id="current_password" label="{{ __('Current Password') }}"
                                    wire:model.live="state.current_password" autocomplete="current-password" />
                            </div>

                            <div class="col-span-6 sm:col-span-12">

                                <x-form.password id="password" label="{{ __('New Password') }}"
                                    wire:model.live="state.password" autocomplete="new-password" />
                            </div>

                            <div class="col-span-6 sm:col-span-12">
                                <x-form.password id="password_confirmation" label="{{ __('Confirm Password') }}"
                                    wire:model.live="state.password_confirmation" autocomplete="new-password" />
                            </div>

                            <div class="col-span-12">
                                <x-errors />
                            </div>


                        </div>

                    </div>

                    <!-- Modal footer -->
                    <div class="px-5 py-4 border-t border-slate-200">
                        <div class="flex flex-wrap justify-end space-x-2">
                            <button class="btn-sm border-slate-200 hover:border-slate-300 text-slate-600"
                                wire:click="closeModal">Cerrar</button>
                            <x-action-message class="mr-3" on="saved-pass">
                                {{ __('Saved.') }}
                                </x-jet-action-message>
                                <button type="submit"
                                    class="btn-sm bg-indigo-500 hover:bg-indigo-600 text-white">Guardar</button>


                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
    <!-- End -->

</div>
