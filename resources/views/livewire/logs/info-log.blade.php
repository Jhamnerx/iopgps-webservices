<x-form.modal.card blur wire:model.live="showModal" align="center" width="4xl">
    @if ($log)
        <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between border-b pb-4">
                <h2 class="text-xl font-bold">Posición enviada a: {{ $log->service_name }} - {{ $log->plate_number }}
                </h2>

            </div>

            <!-- Estado -->
            @if ($log->status == 'error')
                <div class="mt-4 p-4 border rounded-lg bg-red-50 text-red-700">
                    <p>Error al enviar la posición, tipo de envío <strong>Ubicación</strong></p>
                </div>
            @elseif ($log->status == 'success')
                <div class="mt-4 p-4 border rounded-lg bg-green-50 text-green-700">
                    <p>Enviado con éxito, tipo de envío <strong>Ubicación</strong> </p>
                </div>
            @endif


            <!-- Tabla de Fechas -->
            <table class="w-full mt-6 border text-left text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 border-b">Fecha y hora de la posición</th>

                        <th class="py-2 px-4 border-b">Fecha y hora enviada al webservice</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="py-2 px-4 border-b">
                            {{ $log->fecha_hora_posicion ? $log->fecha_hora_posicion->format('d-m-Y H:i:s') : '' }}</td>

                        <td class="py-2 px-4 border-b">
                            {{ $log->created_at ? $log->created_at->format('d-m-Y H:i:s') : '' }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- JSON Respuesta -->
            <div class="mt-6">
                <h3 class="font-bold">Respuesta del webservice:</h3>
                <div class="relative bg-gray-50 border p-4 rounded-lg mt-2 overflow-auto max-h-96">
                    <pre class="text-sm text-gray-700" id="response-json">
                        <code id="formatted-json">{{ json_encode(json_decode($log->response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code>
                    </pre>
                    <button onclick="copyToClipboard('response-json')"
                        class="absolute top-2 right-2 bg-blue-500 text-white text-xs px-2 py-1 rounded hover:bg-blue-600">
                        Copiar
                    </button>
                </div>
            </div>

            <!-- JSON Información enviada -->
            <div class="mt-6">
                <h3 class="font-bold">Información enviada al webservice:</h3>
                <div class="relative bg-gray-50 border p-4 rounded-lg mt-2 overflow-auto max-h-96">
                    <pre class="text-sm text-gray-700" id="sent-json">

                        <code id="formatted-json">{{ json_encode(json_decode($log->request), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code>
                    </pre>
                    <button onclick="copyToClipboard('sent-json')"
                        class="absolute top-2 right-2 bg-blue-500 text-white text-xs px-2 py-1 rounded hover:bg-blue-600">
                        Copiar
                    </button>
                </div>
            </div>

            <!-- Botón de Cerrar -->
            <div class="mt-6 text-right">
                <button id="close-modal-btn" wire:click="closeModal"
                    class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cerrar</button>
            </div>
        </div>
    @endif
</x-form.modal.card>


@push('scripts')
    <script>
        function copyToClipboard(elementId) {
            const text = document.getElementById(elementId).innerText;

            if (navigator.clipboard && navigator.clipboard.writeText) {
                // Usa Clipboard API si está disponible
                navigator.clipboard.writeText(text).then(() => {
                    @this.notifyClient('Texto copiado al portapapeles');
                }).catch((err) => {
                    console.error('Error al copiar:', err);
                    alert('No se pudo copiar el texto al portapapeles');
                });
            } else {
                // Alternativa: copiar manualmente al portapapeles
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed'; // Evitar que se desplace el scroll
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();
                try {
                    document.execCommand('copy');
                    @this.notifyClient('Texto copiado al portapapeles');
                } catch (err) {
                    console.error('Error al copiar:', err);
                    alert('No se pudo copiar el texto al portapapeles');
                }
                document.body.removeChild(textarea);
            }
        }
    </script>
@endpush
