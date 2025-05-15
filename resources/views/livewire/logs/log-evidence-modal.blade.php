<div>
    @if ($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <!-- Modal panel -->
                <div
                    class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100"
                                    id="modal-title">
                                    Evidencia de Logs: {{ $logSummary ? $logSummary->service_name : '' }}
                                </h3>

                                @if ($logSummary)
                                    <div class="mt-4 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                        <div class="grid grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                    Servicio:</p>
                                                <p class="text-gray-600 dark:text-gray-400">
                                                    {{ $logSummary->service_name }}</p>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                    Fecha/Hora:</p>
                                                <p class="text-gray-600 dark:text-gray-400">
                                                    {{ $logSummary->date->format('Y-m-d') }} {{ $logSummary->hour }}:00
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Placa:
                                                </p>
                                                <p class="text-gray-600 dark:text-gray-400">
                                                    {{ $logSummary->plate_number ?: 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">IMEI:
                                                </p>
                                                <p class="text-gray-600 dark:text-gray-400">
                                                    {{ $logSummary->imei ?: 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                    Registros exitosos:</p>
                                                <p class="text-green-600 dark:text-green-400">
                                                    {{ $logSummary->success_count }}</p>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                    Registros con error:</p>
                                                <p class="text-red-600 dark:text-red-400">{{ $logSummary->error_count }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Evidencias de éxito -->
                                        @if ($logSummary->success_samples && count($logSummary->success_samples) > 0)
                                            <div class="mt-6">
                                                <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                                    Evidencias de envíos exitosos
                                                </h4>
                                                <div class="overflow-x-auto">
                                                    <table
                                                        class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                                        <thead class="bg-gray-100 dark:bg-gray-600">
                                                            <tr>
                                                                <th
                                                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                                    Hora
                                                                </th>
                                                                <th
                                                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                                    Estado
                                                                </th>
                                                                <th
                                                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                                    Timestamp
                                                                </th>
                                                                <th
                                                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                                    Identificador
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody
                                                            class="bg-white dark:bg-gray-700 divide-y divide-gray-200 dark:divide-gray-600">
                                                            @foreach ($logSummary->success_samples as $sample)
                                                                <tr>
                                                                    <td
                                                                        class="px-4 py-2 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                                                        {{ $sample['time'] ?? 'N/A' }}
                                                                    </td>
                                                                    <td class="px-4 py-2 whitespace-nowrap text-sm">
                                                                        <span
                                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200">
                                                                            {{ isset($sample['evidence']['status']) ? $sample['evidence']['status'] : 'OK' }}
                                                                        </span>
                                                                    </td>
                                                                    <td
                                                                        class="px-4 py-2 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                                                        {{ $sample['evidence']['timestamp'] ?? 'N/A' }}
                                                                    </td>
                                                                    <td
                                                                        class="px-4 py-2 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                                                        @if (isset($sample['evidence']['id']))
                                                                            ID: {{ $sample['evidence']['id'] }}
                                                                        @elseif(isset($sample['evidence']['token']))
                                                                            Token: {{ $sample['evidence']['token'] }}
                                                                        @else
                                                                            N/A
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                    Mostrando {{ count($logSummary->success_samples) }} de
                                                    {{ $logSummary->success_count }} envíos exitosos
                                                </p>
                                            </div>
                                        @else
                                            <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900 rounded-md">
                                                <p class="text-yellow-700 dark:text-yellow-200">
                                                    No se encontraron evidencias detalladas de envíos exitosos para este
                                                    registro.
                                                </p>
                                            </div>
                                        @endif

                                        <!-- Muestras de errores -->
                                        @if ($logSummary->error_samples && count($logSummary->error_samples) > 0)
                                            <div class="mt-6">
                                                <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                                    Ejemplos de errores
                                                </h4>
                                                <div class="space-y-2">
                                                    @foreach ($logSummary->error_samples as $error)
                                                        <div class="p-3 bg-red-50 dark:bg-red-900/30 rounded-md">
                                                            <div class="flex justify-between">
                                                                <span
                                                                    class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                                                                    {{ $error['time'] ?? 'N/A' }}
                                                                </span>
                                                                <span class="text-xs text-red-600 dark:text-red-400">
                                                                    {{ $error['status'] ?? 'ERROR' }}
                                                                </span>
                                                            </div>
                                                            <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                                                {{ $error['response'] ?? 'Sin detalles' }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="closeModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
