<?php

namespace App\Livewire\Logs;

use App\Models\Logs;
use Illuminate\View\View;
use App\Enums\WebServices;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Rule;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;

final class Tabla extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'TablaLogs';
    public bool $showFilters = true;

    public string $sortField = 'id';

    public string $sortDirection = 'desc';

    public function boot(): void
    {
        config(['livewire-powergrid.filter' => 'outside']);
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::exportable(fileName: 'reenvio-file')
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    public function datasource(): Builder
    {
        return Logs::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('service_name')
            ->add('fecha_hora_enviado', fn($row) => Carbon::parse($row->created_at)->format('Y-m-d H:i:s'))
            ->add('fecha_hora_posicion', fn($row) => $row->fecha_hora_posicion ? Carbon::parse($row->fecha_hora_posicion)->format('Y-m-d H:i:s') : '    ')
            ->add('created_at')
            ->add('plate_number')
            ->add('imei')
            ->add('response')
            ->add('status')
            ->add('method');
    }
    public function columns(): array
    {
        return [
            Column::make('Servicio Web', 'service_name')
                ->sortable()
                ->searchable(),
            Column::make('Fecha/Hora enviado', 'fecha_hora_enviado')
                ->searchable(),

            Column::make('Fecha/Hora posición', 'fecha_hora_posicion')
                ->sortable()
                ->searchable(),

            Column::make('Placa', 'plate_number')
                ->sortable()
                ->searchable(),

            Column::make('Dispositivo', 'imei')
                ->sortable()
                ->searchable(),

            Column::make('Respuesta', 'response')
                ->sortable()
                ->searchable(),

            Column::make('Estatus', 'status')
                ->sortable()
                ->searchable(),

            Column::make('Tipo envío', 'method')
                ->sortable()
                ->searchable(),

            Column::action('Acciones')
        ];
    }
    public function actionsFromView($row): View
    {
        return view('components.actions-view', ['row' => $row]);
    }

    public function filters(): array
    {
        return [
            Filter::datetimepicker('fecha_hora_enviado', 'created_at'),

            Filter::inputText('plate_number', 'plate_number')
                ->operators(['contains', 'is', 'is_not']),

            Filter::inputText('service_name', 'service_name')
                ->operators(['contains', 'is', 'is_not']),

            Filter::enumSelect('service_name', 'service_name')
                ->datasource(WebServices::cases())
                ->optionLabel('wox_logs.service_name'),

            Filter::inputText('imei', 'imei')
                ->operators(['contains', 'is', 'is_not']),
        ];
    }

    public function openModalInfo(Logs $log): void
    {
        $this->dispatch('open-modal-log', log: $log);
    }
}
