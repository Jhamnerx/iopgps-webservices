<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-4 sm:p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                    Resumen de Logs
                </h2>

                <div class="mb-4 flex justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Esta vista muestra un resumen agregado de los logs para mejorar el rendimiento y mantener
                            evidencia de envíos exitosos.
                        </p>
                    </div>
                    <div>
                        <button wire:click="refreshTable"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            <i class="fas fa-sync mr-2"></i> Actualizar
                        </button>
                    </div>
                </div>

                <div class="border rounded-lg">
                    <livewire:logs.summary-table />
                </div>

                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-700 border rounded-lg p-4">
                        <h3 class="text-lg font-semibold mb-3">Información sobre Logs</h3>
                        <div class="space-y-4">
                            <div class="bg-gray-100 dark:bg-gray-600 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-800 dark:text-gray-300">Resumen de Logs</h4>
                                <p class="text-sm mt-2 text-gray-600 dark:text-gray-400">
                                    El sistema agrega automáticamente los logs exitosos para ahorrar espacio en disco,
                                    pero mantiene información detallada de las respuestas exitosas como evidencia.
                                </p>
                            </div>
                            <div class="bg-gray-100 dark:bg-gray-600 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-800 dark:text-gray-300">Políticas de Retención</h4>
                                <p class="text-sm mt-2 text-gray-600 dark:text-gray-400">
                                    <strong>Logs exitosos:</strong> Se mantienen por 7 días en detalle y luego se
                                    conservan como resumen con evidencia de respuesta.<br>
                                    <strong>Logs de error:</strong> Se mantienen por 30 días para facilitar la
                                    resolución de problemas.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-700 border rounded-lg p-4">
                        <h3 class="text-lg font-semibold mb-3">Evidencia de Respuestas</h3>
                        <div class="bg-gray-100 dark:bg-gray-600 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Para cada grupo de envíos exitosos, se guarda la siguiente información como evidencia:
                            </p>
                            <ul class="list-disc pl-5 text-sm text-gray-600 dark:text-gray-400">
                                <li>Timestamp de confirmación del servidor</li>
                                <li>Estado de la respuesta (CREATED, OK, etc.)</li>
                                <li>ID o token único de la transacción</li>
                                <li>Hasta 20 ejemplos de respuestas por hora/placa/imei</li>
                            </ul>
                            <div class="mt-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                                    Haga clic en una fila del resumen para ver los detalles y evidencias de respuestas.
                                </p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button wire:click="optimizeLogs"
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                                {{ $isOptimizing ? 'disabled' : '' }}>
                                <i class="fas fa-broom mr-2"></i> Optimizar Logs
                            </button>
                            <div class="text-xs text-gray-500 mt-1">
                                Ejecuta el proceso de optimización de logs manualmente
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de evidencias -->
    <livewire:logs.log-evidence-modal />
</div>
