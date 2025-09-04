@extends('layout.layout')

@section('title', 'Dashboard')

@section('content')

    <div class="col-sm-12 col-xl-12">

        @include('layout.alerts.success-message')

        @include('layout.alerts.reposos-success')

        @include('layout.alerts.error-message')

        @if (Auth::check() && Auth::user()->cod_cargo == 1)

            

        @endif

@endsection