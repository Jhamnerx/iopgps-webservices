<div>
    {{-- To attain knowledge, add things every day; To attain wisdom, subtract things every day. --}}
</div>
<x-form.modal.card persistent title="Reenvio Data Osinergmin" wire:model.live="showModal" align="center">

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 sm:col-span-6">

            <x-form.datetime.picker wire:model.live="datetimeFrom" label="Desde" placeholder="Fecha Desde" />
        </div>

        <div class="col-span-12 sm:col-span-6">

            <x-form.datetime.picker wire:model.live="datetimeTo" label="Hasta" placeholder="Fecha Hasta" />

        </div>
    </div>

    <x-slot name="footer">
        <div class="flex justify-end gap-x-4">

            <div class="flex gap-2">
                <x-form.button flat label="Cancelar" wire:click="hideModal" />
                <x-form.button primary label="Reenviar data" wire:click.prevent="reenvio" spinner="reenvio" />
            </div>
        </div>
    </x-slot>
</x-form.modal.card>
