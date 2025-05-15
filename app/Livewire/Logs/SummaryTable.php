<?php

namespace App\Livewire\Logs;

use App\Models\LogSummary;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class SummaryTable extends PowerGridComponent
{
    public string $tableName = 'LogSummaryTable';
    public bool $showFilters = true;

    public string $sortField = 'date';
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
        ];
    }

    public function datasource(): Builder
    {
        return LogSummary::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('service_name')
            ->add('date_formatted', fn($row) => Carbon::parse($row->date)->format('Y-m-d'))
            ->add('hour')
            ->add('plate_number')
            ->add('imei')
            ->add('success_count')
            ->add('error_count')
            ->add('total_count')
            ->add('error_rate', fn($row) => $row->total_count > 0 ? round(($row->error_count / $row->total_count) * 100, 1) . '%' : '0%');
    }

    public function columns(): array
    {
        return [
            Column::make('Servicio', 'service_name')
                ->sortable()
                ->searchable(),

            Column::make('Fecha', 'date_formatted', 'date')
                ->sortable()
                ->searchable(),

            Column::make('Hora', 'hour')
                ->sortable()
                ->searchable(),

            Column::make('Placa', 'plate_number')
                ->sortable()
                ->searchable(),

            Column::make('IMEI', 'imei')
                ->sortable()
                ->searchable(),

            Column::make('Exitosos', 'success_count')
                ->sortable(),

            Column::make('Errores', 'error_count')
                ->sortable(),

            Column::make('Total', 'total_count')
                ->sortable(),

            Column::make('% Error', 'error_rate')
                ->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::datepicker('date'),

            Filter::inputText('service_name', 'service_name')
                ->operators(['contains', 'is', 'is_not']),

            Filter::inputText('plate_number', 'plate_number')
                ->operators(['contains', 'is', 'is_not']),

            Filter::inputText('imei', 'imei')
                ->operators(['contains', 'is', 'is_not']),
        ];
    }

    public function onRowClick($row): void
    {
        // Cuando un usuario hace clic en una fila, emitimos un evento para mostrar el modal con la evidencia
        $this->dispatch('showLogEvidence', $row['id']);
    }
}
