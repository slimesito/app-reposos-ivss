@extends('layout.layout')

@section('title', '14-73 Enfermedad')

@section('content')

    <div class="col-sm-12 col-xl-12">

        @include('layout.alerts.success-message')

        @include('layout.alerts.reposos-success')

        @include('layout.alerts.error-message')

    </div>

    <div class="col-sm-12 col-xl-12">

        <div class="bg-secondary rounded h-100 p-4">

            <h6 class="mb-4">Crear nuevo Reposo 14-73</h6>

            <form method="POST" action="{{ route('create.reposo.enfermedad') }}" enctype="multipart/form-data">

                @csrf

                <div class="mb-3">
                    <label for="posee_examenes" class="form-label">¿Posee Exámenes?:</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="posee_examenes" id="inlineRadio1" value="1" checked>
                        <label class="form-check-label" for="inlineRadio1">Sí</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="posee_examenes" id="inlineRadio2" value="0">
                        <label class="form-check-label" for="inlineRadio2">No</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="convalidado" class="form-label">¿Es convalidado?:</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="convalidado" id="inlineRadio1" value="1">
                        <label class="form-check-label" for="inlineRadio1">Sí</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="convalidado" id="inlineRadio2" value="0" checked>
                        <label class="form-check-label" for="inlineRadio2">No</label>
                    </div>
                </div>

                <div class="mb-3">

                    <label for="id_capitulo" class="form-label">Capítulo:</label>

                    <select name="id_capitulo" class="form-select mb-3" id="id_capitulo" aria-label="Seleccione el Capítulo">
                        <option hidden selected disabled>Seleccione el Capítulo</option>
                        @foreach($capitulos as $capitulo)
                            <option value="{{ $capitulo->id }}">{{ $capitulo->descripcion }}</option>
                        @endforeach
                    </select>

                </div>

                <div class="mb-3">
                    <label for="id_pat_general" class="form-label">Patología General:</label>
                    <select name="id_pat_general" class="form-select mb-3" id="id_pat_general" aria-label="Seleccione la Patología General" disabled>
                        <option hidden selected disabled>Seleccione la Patología General</option>
                    </select>
                </div>          

                <div class="mb-3">
                    <label for="id_pat_especifica" class="form-label">Patología Específica:</label>
                    <select name="id_pat_especifica" class="form-select mb-3" id="id_pat_especifica" aria-label="Seleccione la Patología Específica" disabled>
                        <option hidden selected disabled>Seleccione la Patología Específica</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="exampleInputEmail1" class="form-label">Incapacidad por:</label>
                    <select name="incapacidad_por" placeholder="Seleccione el Motivo" class="form-select mb-3" id="incapacidad_por" aria-label="Default select example">
                        <option hidden selected disabled>Seleccione</option>
                        <option value="AMBULATORIO">Ambulatorio</option>
                        <option value="HOSPITALIZACION">Hospitalización</option>
                    </select>
                </div>

                <div class="mb-3">

                    <label for="exampleInputEmail1" class="form-label">Motivo:</label>
                    
                    <select name="cod_motivo" placeholder="Seleccione el Motivo" class="form-select mb-3" id="cod_motivo" aria-label="Default select example">
                        
                        <option hidden selected disabled>Seleccione el Motivo</option>
                        <option value="1">Enfermedad Común</option>
                        <option value="4">Enfermedad Profesional</option>
                        <option value="3">Accidente Común</option>
                        <option value="5">Accidente Laboral</option>

                    </select>

                </div>

                <div class="mb-3">
                    <label for="inicio_reposo" class="form-label">Inicio del Reposo:</label>
                    <input type="date" name="inicio_reposo" class="form-control" id="inicio_reposo" required>
                </div>

                <div class="mb-3">
                    <label for="fin_reposo" class="form-label">Fin del Reposo:</label>
                    <input type="date" name="fin_reposo" class="form-control" id="fin_reposo" required>
                </div>

                <div class="mb-3">
                    <label for="reintegro" class="form-label">Reintegro:</label>
                    <input type="date" name="reintegro" class="form-control" id="reintegro" required>
                </div>

                <div class="mb-3">
                    <label for="tlf_movil" class="form-label">Teléfono Móvil:</label>
                    <input type="number" name="tlf_movil" class="form-control" id="tlf_movil>
                </div>

                <div class="mb-3">
                    <label for="tlf_habitacion" class="form-label">Teléfono Habitación:</label>
                    <input type="number" name="tlf_habitacion" class="form-control" id="tlf_habitacion">
                </div>

                <div class="mb-3">
                    <label for="tlf_oficina" class="form-label">Teléfono Oficina:</label>
                    <input type="number" name="tlf_oficina" class="form-control" id="tlf_oficina">
                </div>

                <div class="mb-3">
                    <label for="email_trabajador" class="form-label">Correo Electrónico del Trabajador:</label>
                    <input type="text" name="email_trabajador" class="form-control" id="email_trabajador">
                </div>

                <div class="mb-3">
                    <label for="email_jefe_inmediato" class="form-label">Correo Electrónico del Jefe Inmediato:</label>
                    <input type="text" name="email_jefe_inmediato" class="form-control" id="email_jefe_inmediato">
                </div>

                <div class="mb-3">
                    <label for="observaciones" class="form-label">Observaciones:</label>
                    <input type="text" name="observaciones" class="form-control" id="observaciones">
                </div>

                <div class="mb-3">
                    <label for="convalidado" class="form-label">¿Debe volver?:</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="debe_volver" id="inlineRadio1" value="1">
                        <label class="form-check-label" for="inlineRadio1">Sí</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="debe_volver" id="inlineRadio2" value="0">
                        <label class="form-check-label" for="inlineRadio2">No</label>
                    </div>
                </div>
                
                <button class="btn btn-lg btn-primary m-2">Registrar</button>

            </form>

        </div>

    </div>

@endsection
