<x-form.modal.card blur wire:model.live="showModal" align="center" width="3xl" max-width="3xl" class="mx-auto">
    @if ($device ?? null)
        <div class="bg-white rounded-lg shadow-lg w-full p-5 max-w-3xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between border-b pb-3 mb-3">
                <h2 class="text-lg font-bold text-gray-800">
                    <span class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                        {{ $device->plate }} <span class="text-sm text-gray-500 ml-1">({{ $device->imei }})</span>
                    </span>
                </h2>
                <div>
                    <button wire:click="updateAccountInfo" wire:loading.attr="disabled"
                        class="px-2.5 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center transition-colors">
                        <span wire:loading.remove wire:target="updateAccountInfo">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Actualizar
                        </span>
                        <span wire:loading wire:target="updateAccountInfo" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Actualizando...
                        </span>
                    </button>
                </div>
            </div>

            <!-- Estado de actualización -->
            @if ($updateResult)
                @if ($updateResult['success'])
                    <div class="mt-2 mb-2 p-2 border rounded bg-green-50 text-green-700 text-xs">
                        <div class="flex items-center">
                            <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>{{ $updateResult['message'] }}</span>
                        </div>
                    </div>
                @else
                    <div class="mt-2 mb-2 p-2 border rounded bg-red-50 text-red-700 text-xs">
                        <div class="flex items-center">
                            <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span>{{ $updateResult['message'] }}</span>
                        </div>
                    </div>
                @endif
            @endif

            <!-- Información del Dispositivo -->
            <div class="mt-3">
                <h3 class="flex items-center text-sm font-bold text-gray-700 border-b pb-1.5 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1.5 text-blue-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Detalles del Dispositivo
                </h3>
                <div class="grid grid-cols-2 gap-2 bg-gray-50 rounded-lg p-2.5">
                    <div class="px-2 py-0.5 border-b border-gray-100">
                        <p class="text-xs text-gray-500 uppercase">IMEI</p>
                        <p class="font-medium text-sm">{{ $device->imei }}</p>
                    </div>
                    <div class="px-2 py-0.5 border-b border-gray-100">
                        <p class="text-xs text-gray-500 uppercase">Placa</p>
                        <p class="font-medium text-sm">{{ $device->plate ?: 'No disponible' }}</p>
                    </div>
                    <div class="px-2 py-0.5 border-b border-gray-100">
                        <p class="text-xs text-gray-500 uppercase">Nombre</p>
                        <p class="font-medium text-sm">{{ $device->name ?: 'No disponible' }}</p>
                    </div>
                    <div class="px-2 py-0.5 border-b border-gray-100">
                        <p class="text-xs text-gray-500 uppercase">Número de WhatsApp</p>
                        <p class="font-medium text-sm">{{ $device->number_whatsapp ?: 'No disponible' }}</p>
                    </div>
                    <div class="px-2 py-0.5">
                        <p class="text-xs text-gray-500 uppercase">Última Actualización</p>
                        <p class="font-medium text-sm">
                            {{ $device->last_update ? $device->last_update->format('d/m/Y H:i:s') : 'No disponible' }}
                        </p>
                    </div>
                    <div class="px-2 py-0.5">
                        <p class="text-xs text-gray-500 uppercase">Última Actualización de Detalles</p>
                        <p class="font-medium text-sm">
                            {{ $device->details_last_updated ? $device->details_last_updated->format('d/m/Y H:i:s') : 'Nunca' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Información de la Cuenta -->
            <div class="mt-4">
                <h3 class="flex items-center text-sm font-bold text-gray-700 border-b pb-1.5 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1.5 text-blue-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Detalles de la Cuenta Asociada
                </h3>
                @if ($account)
                    <div class="grid grid-cols-2 gap-2 bg-gray-50 rounded-lg p-2.5">
                        <div class="px-2 py-0.5 border-b border-gray-100">
                            <p class="text-xs text-gray-500 uppercase">ID de Cuenta</p>
                            <p class="font-medium text-sm">{{ $account->accountId }}</p>
                        </div>
                        <div class="px-2 py-0.5 border-b border-gray-100">
                            <p class="text-xs text-gray-500 uppercase">Nombre de Usuario</p>
                            <p class="font-medium text-sm">{{ $account->userName }}</p>
                        </div>
                        <div class="px-2 py-0.5 border-b border-gray-100">
                            <p class="text-xs text-gray-500 uppercase">Cuenta</p>
                            <p class="font-medium text-sm">{{ $account->account }}</p>
                        </div>
                        <div class="px-2 py-0.5 border-b border-gray-100">
                            <p class="text-xs text-gray-500 uppercase">Contacto</p>
                            <p class="font-medium text-sm">{{ $account->contactUser ?: 'No disponible' }}</p>
                        </div>
                        <div class="px-2 py-0.5 border-b border-gray-100">
                            <p class="text-xs text-gray-500 uppercase">Teléfono</p>
                            <p class="font-medium text-sm">{{ $account->contactTel ?: 'No disponible' }}</p>
                        </div>
                        <div class="px-2 py-0.5 border-b border-gray-100">
                            <p class="text-xs text-gray-500 uppercase">Email</p>
                            <p class="font-medium text-sm">{{ $account->email ?: 'No disponible' }}</p>
                        </div>
                        <div class="col-span-2 px-2 py-0.5 border-b border-gray-100">
                            <p class="text-xs text-gray-500 uppercase">Dirección</p>
                            <p class="font-medium text-sm">{{ $account->address ?: 'No disponible' }}</p>
                        </div>
                        <div class="px-2 py-0.5">
                            <p class="text-xs text-gray-500 uppercase">Contacto de Renovación</p>
                            <p class="font-medium text-sm">{{ $account->renewalContact ?: 'No disponible' }}</p>
                        </div>
                        <div class="px-2 py-0.5">
                            <p class="text-xs text-gray-500 uppercase">Última Actualización</p>
                            <p class="font-medium text-sm">
                                {{ $account->details_last_updated ? $account->details_last_updated->format('d/m/Y H:i:s') : 'Nunca' }}
                            </p>
                        </div>
                    </div>

                    @if ($account->additional_data)
                        <div class="mt-3">
                            <h4 class="flex items-center text-xs font-semibold text-gray-600 mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1 text-blue-500"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Datos Adicionales:
                            </h4>
                            <div
                                class="bg-gray-50 border border-gray-200 p-2 rounded-lg mt-1 overflow-auto max-h-28 text-xs">
                                <pre class="whitespace-pre-wrap">{{ is_string($account->additional_data) ? $account->additional_data : json_encode($account->additional_data, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="p-3 bg-gray-50 border border-gray-200 text-gray-600 rounded-lg flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-blue-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="font-medium text-sm">No hay cuenta asociada a este dispositivo.</p>
                            <p class="mt-1 text-xs">Haz clic en "Actualizar" para obtener los datos desde la API.</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Botón de Cerrar -->
            <div class="mt-6 flex justify-end">
                <button id="close-modal-btn" wire:click="closeModal"
                    class="px-3 py-1.5 text-xs bg-gray-200 text-gray-800 rounded hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 transition-colors">
                    Cerrar
                </button>
            </div>
        </div>
    @endif
</x-form.modal.card>
