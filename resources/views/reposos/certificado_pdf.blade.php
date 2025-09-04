<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F-14-73 | {{ config('app.name') }}</title>
    <style>
        {{ file_get_contents(public_path('assets/css/certificadoStyles.css')) }}
    </style>
    <link href="{{ public_path('assets/logo.png') }}" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ public_path('assets/logo.png') }}" alt="Logo IVSS">
            <p>REPÚBLICA BOLIVARIANA DE VENEZUELA</p>
            <p>MINISTERIO DEL PODER POPULAR PARA EL PROCESO SOCIAL DE TRABAJO</p>
            <p>INSTITUTO VENEZOLANO DE LOS SEGUROS SOCIALES</p>
            <p>DIRECCIÓN GENERAL DE AFILIACIÓN Y PRESTACIONES DE DINERO</p>
        </div>
        <div class="section">
            @if ($reposo->es_enfermedad == 1)
                <h1>CERTIFICADO DE INCAPACIDAD TEMPORAL 14-73</h1>
            @elseif ($reposo->es_prenatal == 1 || $reposo->es_postnatal == 1)
                <h1>CERTIFICADO DE PERMISO POR MATERNIDAD 14-73</h1>
            @endif
        </div>
        <div class="section">
            <table class="left-table">
                <tr>
                    <th>FECHA DE ELABORACIÓN:</th>
                    <th>N°:</th>
                </tr>
                <tr>
                    <td>{{ $fecha_elaboracion }}</td>
                    <td>{{ $reposo->numero_ref_reposo }}</td>
                </tr>
            </table>
            <table>
                <tr>
                    <th>CENTRO ASISTENCIAL:</th>
                    <th>CONSULTA O SERVICIO:</th>
                </tr>
                <tr>
                    <td>{{ $usuario->centroAsistencial->nombre }}</td>
                    <td>{{ $usuario->servicio->nombre }}</td>
                </tr>
            </table>
        </div>
        <div class="section">
            <table>
                <tr>
                    <th>CÉDULA DE IDENTIDAD:</th>
                    <th>APELLIDOS Y NOMBRES DEL (DE LA) ASEGURADO (A):</th>
                    <th>NACIMIENTO:</th>
                    <th>GÉNERO:</th>
                </tr>
                <tr>
                    <td>{{ $cedula_formateada }}</td>
                    <td>{{ $ciudadano->primer_nombre }} {{ $ciudadano->segundo_nombre }} {{ $ciudadano->primer_apellido }} {{ $ciudadano->segundo_apellido }}</td>
                    <td>{{ \Carbon\Carbon::parse($ciudadano->fecha_nacimiento)->format('d/m/Y') }}</td>
                    <td>{{ $ciudadano->sexo }}</td>
                </tr>
            </table>
            <table>
                <tr>
                    <th colspan="3">NÚMERO TELEFÓNICO DEL ASEGURADO(A):</th>
                    <th colspan="3">CORREO EMPLEADO:</th>
                    <th>CORREO JEFE INMEDIATO:</th>
                    <th>CORREO EMPLEADOR:</th>
                    <th>FECHA REINTEGRO:</th>
                </tr>
                <tr>
                    <th>HABITACIÓN:</th>
                    <th>OFICINA</th>
                    <th>MÓVIL</th>
                    <td rowspan="2" colspan="3">{{ $reposo->email_trabajador }}</td>
                    <td rowspan="2">{{ $reposo->email_jefe_inmediato }}</td>
                    <td rowspan="2">CORREO@EMPLEADOR.COM</td>
                    <td rowspan="2">{{ \Carbon\Carbon::parse($reposo->reintegro)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td>{{ $reposo->tlf_habitacion }}</td>
                    <td>{{ $reposo->tlf_oficina }}</td>
                    <td>{{ $reposo->tlf_movil }}</td>
                </tr>
            </table>
        </div>
        <div class="section">
            <table>
                <tr>
                    <th rowspan="1">INCAPACIDAD POR:</th>
                    <th colspan="3">CONCEPTO</th>
                    <th colspan="3" rowspan="1">PERIODO DE INCAPACIDAD</th>
                    <th rowspan="1">PRE-NATAL</th>
                    <th rowspan="1">POST-NATAL</th>
                    <th rowspan="1">DEBE VOLVER</th>
                </tr>
                <tr>
                    <td rowspan="2">{{ $reposo->incapacidad_por }}</th>
                    <td colspan="3" rowspan="2">{{ $reposo->motivo->descripcion }}</td>
                    <th>DESDE</th>
                    <th>HASTA</th>
                    <th>NÚMERO DE DÍAS</th>
                    <td rowspan="2">{{ $reposo->es_prenatal ? 'SÍ' : 'NO' }}</td>
                    <td rowspan="2">{{ $reposo->es_postnatal ? 'SÍ' : 'NO' }}</td>
                    <td rowspan="2">{{ $reposo->debe_volver ? 'SÍ' : 'NO' }}</td>
                </tr>
                <tr>
                    <td>{{ \Carbon\Carbon::parse($reposo->inicio_reposo)->format('d/m/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($reposo->fin_reposo)->format('d/m/Y') }}</td>
                    <td>{{ $reposo->dias_indemnizar }}</td>
                </tr>
            </table>
            <table>
                <tr>
                    <th>CANTIDAD DE REPOSOS</th>
                    <th colspan="9">DIAGNÓSTICO EN LETRAS</th>
                    <th>TIPO DE REPOSO</th>
                    <th>CÓDIGO DIAGNÓSTICO</th>
                </tr>
                <tr>
                    <td rowspan="2">{{ $expediente->cantidad_reposos }}</td>
                    <td colspan="9">{{ $reposo->patologiaGeneral->descripcion }}</td>
                    <td rowspan="2">{{ $reposo->convalidado ? 'CONVALIDADO' : 'OTORGADO' }}</td>
                    <td rowspan="2">{{ $reposo->patologiaGeneral->pat_general_id }}</td>
                </tr>
                <tr>
                    <td colspan="9">{{ $reposo->patologiaEspecifica->descripcion ?? 'NA' }}</td>
                </tr>
            </table>
        </div>
        <div class="section">
            <table>
                <tr>
                    <th colspan="3" rowspan="2">OBSERVACIONES</th>
                    <th colspan="4" rowspan="2">MÉDICO RESPONSABLE</th>
                    <th colspan="3">DIRECTOR(A) DEL CENTRO ASISTENCIAL</th>
                </tr>
                <tr>
                    <td colspan="3">(EN CASO DE INCAPACIDAD MAYOR A 21 DÍAS POR ENFERMEDAD O ACCIDENTES)</td>
                </tr>
                <tr>
                    <td colspan="3" rowspan="4">{{ $reposo->observaciones }}</td>
                    <td colspan="4">{{ $usuario->nombres }} {{ $usuario->apellidos }}</td>
                    <td colspan="3">NOMBRES Y APELLIDOS</td>
                </tr>
                <tr>
                    <th colspan="3">CÉDULA DE IDENTIDAD</td>
                    <th>N° REGISTRO MPPS:</td>
                    <th colspan="3">CÉDULA DE IDENTIDAD</td>
                </tr>
                <tr>
                    <td colspan="3">{{ $usuario->cedula }}</td>
                    <td>{{ $usuario->nro_mpps }}</td>
                    <td colspan="3">98765432</td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center;"><img src="{{ public_path('storage/app/assets/firmas/' . basename(Auth::user()->firma)) }}" width="150"></td>
                    <td colspan="2" style="text-align: center;"><img src="{{ public_path('storage/app/assets/sellos/' . basename(Auth::user()->sello)) }}" width="100"></td>
                    <td colspan="2" style="text-align: center;"><img src="{{ public_path('storage/app/assets/firmas/' . basename(Auth::user()->firma)) }}" width="180"></td>
                    <td colspan="1" style="text-align: center;"><img src="{{ public_path('storage/app/assets/sellos/' . basename(Auth::user()->sello)) }}" width="100"></td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
