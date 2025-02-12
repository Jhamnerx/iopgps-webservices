@extends('layouts.app')


@section('headerVariant', 'v2')
@section('sidebarVariant', 'v2')
@section('panel', "settingsPanel: 'account',")

@section('contenido')

    <!-- Table -->

    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full mx-auto">

        <!-- Page header -->
        <div class="mb-8">

            <!-- Title -->
            <h1 class="text-2xl md:text-3xl text-slate-800 font-bold"> Ajustes ✨</h1>

        </div>

        <div class="bg-white shadow-lg rounded-sm mb-8">
            <div class="flex flex-col md:flex-row md:-mr-px">

                <!-- Sidebar -->

                <x-app.navigation></x-app.navigation>

                <!-- Panel -->
                @livewire('ajustes.cuenta.update-profile-information')

            </div>
        </div>

    </div>



@stop

@push('modals')
@endpush


{{-- section de js --}}
@section('js')

@stop
