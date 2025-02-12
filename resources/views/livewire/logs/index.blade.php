<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto" wire:poll.60s="refreshTable">

    <!-- Page header -->
    <div class="sm:flex sm:justify-between sm:items-center mb-5">

        <!-- Left: Title -->
        <div class="mb-4 sm:mb-0">
            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Logs</h1>
        </div>

        <!-- Right: Actions -->
        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">


        </div>

    </div>

    <!-- More actions -->
    <div class="sm:flex sm:justify-between sm:items-center mb-5">

        <!-- Right side -->
        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
            <!-- Add button -->
            <x-form.button wire:click="export" spinner="export" positive rounded="md" label="Exportar" />
        </div>

    </div>

    <!-- Table -->
    <livewire:logs.tabla />

</div>
