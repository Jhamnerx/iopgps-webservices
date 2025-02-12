@extends('layouts.app')


@section('headerVariant', 'v2')
@section('sidebarVariant', 'v2')


@section('contenido')

    @livewire('logs.index')

@stop

@push('modals')
    @livewire('logs.info-log')
@endpush


{{-- section de js --}}
@section('js')

@stop
