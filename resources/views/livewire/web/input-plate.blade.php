<div class="font-medium text-slate-800 dark:text-slate-100">
    <x-form.input wire:model.live="unit.plate">
        <x-slot name="append">
            <x-form.button class="h-full" icon="document-plus" rounded="rounded-r-md" primary flat maxlength="7"
                wire:click="updatePlate('{{ $unit['plate'] }}',{{ $unit['id'] }} )" />
        </x-slot>
    </x-form.input>
</div>
