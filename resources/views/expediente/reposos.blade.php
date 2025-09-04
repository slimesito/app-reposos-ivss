@extends('layout.layout')

@section('title', 'Reposos')

@section('content')

    <div class="col-sm-12 col-xl-12">

        @include('layout.alerts.success-message')

        @include('layout.alerts.reposos-success')

        @include('layout.alerts.error-message')

    </div>

    <div class="col-sm-12 col-xl-12">

        <div class="bg-secondary rounded h-100 p-4">

            <h6 class="mb-4">Reposos Registrados</h6>

            <form action="{{ route('buscador.reposos') }}" method="GET" class="d-none d-md-flex ms-4">
                <input class="form-control bg-dark border-0" type="search" name="repososQuery" placeholder="Buscar Reposos">
            </form>

            <br>

            <div class="table-responsive">

                <table class="table table-hover">

                    <thead>

                        <tr>
                            <th>Cédula</th>
                            <th>Especialidad</th>
                            <th>Capítulo</th>
                            <th>Patología General</th>
                            {{-- <th>Patología Específica</th> --}}
                            <th>Fecha</th>
                            <th style="text-align: center;">PDF</th>
                            <th>Acciones</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach($reposos as $reposo)

                            <tr>
                                <td>{{ $reposo->cedula_formateada }}</td>
                                <td>{{ $reposo->servicio->nombre }}</td>
                                <td>{{ $reposo->capitulo->capitulo_id }}</td>
                                <td>{{ $reposo->patologiaGeneral->descripcion }}</td>
                                {{-- <td>{{ $reposo->patologiaEspecifica->descripcion ?? 'N/A' }}</td> --}}
                                <td>{{ \Carbon\Carbon::parse($reposo->fecha_create)->format('d/m/Y h:i A') }}</td>
                                <td>
                                    <a href="{{ route('descargar.reposo.pdf', $reposo->id) }}" class="btn btn-warning rounded-pill m-2" title="Descargar en PDF"><i class="fa-solid fa-download"></i></a>
                                </td>
                                <td>
                                    <form action="{{ route('eliminar.reposo', $reposo->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-danger rounded-pill m-2" title="Eliminar"
                                                onclick="return confirm('¿Estás seguro de querer eliminar este reposo?')">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                        @endforeach

                    </tbody>

                </table>

                {{ $reposos->links() }}

            </div>

        </div>

    </div>

@endsection
