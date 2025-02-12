<div class="container mx-auto col-span-full"wire:poll.30s="actualizarVista">
    <div class="flex justify-center">
        <div class="w-full md:w-11/12">
            <div class="bg-white shadow-md rounded-lg dark:bg-gray-800">
                <!-- Header -->
                <div class="flex items-center bg-gray-100 px-6 py-4 rounded-t-lg dark:bg-gray-700">
                    <div class="text-blue-500 text-3xl mr-4">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Dashboard</h3>
                </div>
                <!-- Body -->
                <div class="p-6">
                    <div class="flex flex-wrap justify-center">
                        <!-- Tramas Transmitidas -->
                        <div class="w-full sm:w-1/2 md:w-1/3 px-4 mb-6">
                            <div class="bg-white shadow-md rounded-lg p-4 dark:bg-gray-700">
                                <div class="flex flex-col items-center">
                                    <div class="text-blue-500 text-3xl mb-2">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-700 text-center mb-2 dark:text-gray-300">
                                        Tramas transmitidas
                                    </h3>
                                    <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-100">
                                        {{ $data['success'] }}
                                    </h1>
                                </div>
                            </div>
                        </div>
                        <!-- Estado API -->
                        <div class="w-full sm:w-1/2 md:w-1/4 px-4 mb-6">
                            <div class="bg-white shadow-md rounded-lg p-4 dark:bg-gray-700">
                                <div class="flex flex-col items-center">
                                    <div class="text-blue-500 text-3xl mb-2">
                                        <i class="fas fa-terminal"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-700 text-center mb-2 dark:text-gray-300">
                                        Estado API
                                    </h3>
                                    <div class="bg-green-500 text-white text-center rounded py-1 px-4">
                                        <h3 class="font-bold text-sm">ACTIVO</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Tramas Rechazadas -->
                        <div class="w-full sm:w-1/2 md:w-1/4 px-4 mb-6">
                            <div class="bg-white shadow-md rounded-lg p-4 dark:bg-gray-700">
                                <div class="flex flex-col items-center">
                                    <div class="text-red-500 text-3xl mb-2">
                                        <i class="fas fa-times-circle"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-700 text-center mb-2 dark:text-gray-300">
                                        Tramas rechazadas
                                    </h3>
                                    <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-100">
                                        {{ $data['failed'] }}</h1>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
