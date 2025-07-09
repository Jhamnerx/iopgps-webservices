<div>
    <x-form.button right-icon="clipboard" positive hover="success" focus:solid.green
        wire:click.prevent="openModalInfo({{ $row->id }})" spinner="sleeping" />
</div>
