@extends('layout.layout')

@section('title', 'Aprobar Usuarios')

@section('content')

    <div class="col-sm-12 col-xl-12">

        @include('layout.alerts.success-message')

        @include('layout.alerts.reposos-success')

        @include('layout.alerts.error-message')

    </div>

    <div class="col-sm-12 col-xl-12">

        <div class="bg-secondary rounded h-100 p-4">
            <h6 class="mb-4">Usuarios por Aprobar</h6>

            <form action="{{ route('buscador.usuarios.aprobar') }}" method="GET" class="d-none d-md-flex ms-4">
                <input class="form-control bg-dark border-0" type="search" name="aprobarUsuariosQuery" placeholder="Buscar">
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th scope="col">Nombres</th>
                            <th scope="col">Apellidos</th>
                            <th scope="col">Cédula</th>
                            <th scope="col">Número MPPS</th>
                            <th scope="col">Cargo</th>
                            <th scope="col">Servicio</th>
                            <th scope="col">C. Asistencial</th>
                            <th scope="col">Sello</th>
                            <th scope="col">Firma</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($usuarios as $usuario)
                            <tr>
                                <td>{{ $usuario->nombres }}</td>
                                <td>{{ $usuario->apellidos }}</td>
                                <td>{{ $usuario->cedula }}</td>
                                <td>{{ $usuario->nro_mpps }}</td>
                                <td>{{ $usuario->cod_cargo }}</td>
                                <td>{{ $usuario->servicio ? $usuario->servicio->nombre : 'N/A' }}</td>
                                <td>{{ $usuario->centroAsistencial ? $usuario->centroAsistencial->nombre : 'N/A' }}</td>
                                <td>
                                    @if($usuario->sello)
                                        <img src="{{ Storage::url($usuario->sello) }}" alt="Sello del usuario" height="50" width="50">
                                    @else
                                        Sin sello
                                    @endif
                                </td>
                                <td>
                                    @if($usuario->firma)
                                        <img src="{{ Storage::url($usuario->firma) }}" alt="Firma del usuario" height="50" width="50">
                                    @else
                                        Sin firma
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <!-- Botón para editar -->
                                        <form action="{{ route('aprobar.usuario', $usuario->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-warning rounded-pill m-1" onclick="return confirm('¿Estás seguro de que deseas aprobar a este usuario?')"><i class="fa-solid fa-check" title="Aceptar"></i></button>
                                        </form>

                                        <!-- Botón para eliminar -->

                                        <form id="delete-form-Usuario-{{ $usuario->id }}" action="{{ route('rechazar.usuario', $usuario->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-danger rounded-pill m-1" onclick="confirmDelete({{ $usuario->id }}, 'Usuario')"><i class="fa-solid fa-xmark" title="Rechazar"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $usuarios->links() }}
            </div>
        </div>

    </div>

@endsection
