<?php

namespace App\Http\Controllers\Reposos;

use App\Http\Controllers\Controller;
use App\Models\AseguradoEmpresa;
use App\Models\Capitulo;
use App\Models\CentroAsistencial;
use App\Models\Ciudadano;
use App\Models\Empresa;
use App\Models\Expediente;
use App\Models\Lugar;
use App\Models\Motivo;
use App\Models\PatologiaEspecifica;
use App\Models\PatologiaGeneral;
use App\Models\Reposo;
use App\Models\Servicio;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RepososEnfermedadController extends Controller
{
    public function validarCedulaReposoEnfermedadView()
    {
        return view('reposos.validar_cedula');
    }

    public function validarCedulaReposoEnfermedad(Request $request)
    {
        $cedula = $request->input('cedula');
        $nacionalidad = $request->input('nacionalidad');

        $formattedCedula = ($nacionalidad == 1 ? '1' : '2') . str_pad($cedula, 9, '0', STR_PAD_LEFT);

        $asegurado = AseguradoEmpresa::where('id_asegurado', $formattedCedula)->first();
        if (!$asegurado) {
            return redirect()->back()->withErrors(['cedula' => 'No se encontró ningún asegurado con esa cédula.']);
        }

        $prefijo = substr($formattedCedula, 0, 1) === '1' ? 'V' : 'E';
        $cedulaAjustada = $prefijo . substr($formattedCedula, 1);

        $ciudadano = Ciudadano::where('id_ciudadano', $cedulaAjustada)->first();
        if (!$ciudadano) {
            return redirect()->back()->withErrors(['cedula' => 'No se encontró información del ciudadano.']);
        }

        $sexoCiudadano = $ciudadano->sexo;

        $fechaNacimiento = new \DateTime($ciudadano->fecha_nacimiento);
        $hoy = new \DateTime();
        $edad = $hoy->diff($fechaNacimiento)->y;

        if (($ciudadano->sexo === 'F' && $edad >= 55) || ($ciudadano->sexo === 'M' && $edad >= 60)) {
            return redirect()->back()->withErrors(['cedula' => 'El/La ciudadano/a es adulto/a mayor.']);
        }

        session([
            'cedula' => $formattedCedula,
            'sexoAsegurado' => $sexoCiudadano,
        ]);
        return redirect()->route('nuevo.reposo.enfermedad.view');
    }

    public function nuevoReposoEnfermedadView()
    {
        $servicios = Servicio::all();
        $capitulos = Capitulo::where('id', '!=', 16)->get();
        $patologiasGenerales = PatologiaGeneral::all();
        $patologiasEspecificas = PatologiaEspecifica::all();
        $lugares = Lugar::all();
        $motivos = Motivo::all();

        Log::info('Patologías Generales:', ['patologiasGenerales' => $patologiasGenerales]);
        return view('reposos.nuevo_reposo_enfermedad', compact('servicios', 'capitulos', 'patologiasGenerales', 'patologiasEspecificas', 'lugares', 'motivos'));
    }

    public function getPatologiasGenerales($id)
    {
        $patologiasGenerales = PatologiaGeneral::where('capitulo_id', $id)->get();
        return response()->json($patologiasGenerales);
    }

    public function createReposoEnfermedad(Request $request)
    {
        $request->validate([
            'id_capitulo' => 'required|numeric',
            'id_pat_general' => 'required|numeric',
            'id_pat_especifica' => 'numeric',
            'cod_motivo' => 'required|numeric',
            'inicio_reposo' => 'required|date',
            'fin_reposo' => 'required|date',
            'reintegro' => 'required|date',
            'debe_volver' => 'required|boolean',
            'observaciones' => 'nullable|string',
            'email_trabajador' => 'required|email',
        ]);

        try {
            DB::transaction(function () use ($request) {
                // $maxId = DB::table('reposos')->max('id'); // <-- LÍNEA ELIMINADA

                $cedula = session('cedula');
                $sexoAsegurado = session('sexoAsegurado');
                $usuario = auth()->user();

                $prefijo = substr($cedula, 0, 1) === '1' ? 'V' : (substr($cedula, 0, 1) === '2' ? 'E' : null);
                $cedulaAjustada = $prefijo ? $prefijo . substr($cedula, 1) : $cedula;

                $ciudadano = Ciudadano::where('id_ciudadano', $cedulaAjustada)->first();

                $aseguradoEmpresa = AseguradoEmpresa::where('id_asegurado', $cedula)
                                    ->where('id_estatus_asegurado', 'A')
                                    ->whereNotNull('id_empresa')
                                    ->whereNotNull('id_asegurado')
                                    ->first();

                $emailEmpresa = null;
                if ($aseguradoEmpresa && $aseguradoEmpresa->id_empresa) {
                    $empresa = Empresa::where('id_empresa', $aseguradoEmpresa->id_empresa)->first();
                    if ($empresa) {
                        $emailEmpresa = $empresa->email_principal;
                    }
                }

                $idAsegurado = $aseguradoEmpresa->id_asegurado;
                $idEmpresa = $aseguradoEmpresa->id_empresa;

                if (!$idEmpresa) {
                    throw new \Exception('No se encontró la empresa asociada al asegurado.');
                }

                $expediente = Expediente::firstOrNew(['cedula' => $cedula]);
                if ($expediente->exists) {
                    $expediente->cantidad_reposos += 1;
                    $expediente->id_update = auth()->user()->id;
                    $expediente->fecha_update = now();
                    $totalDiasIndemnizar = Reposo::where('cedula', $cedula)->sum('dias_indemnizar');
                    $expediente->dias_acumulados = $totalDiasIndemnizar;
                } else {
                    $expediente->cantidad_reposos = 1;
                    $expediente->cantidad_prorrogas = 0;
                    $expediente->dias_acumulados = 0;
                    $expediente->semanas_acumuladas = 0;
                    $expediente->dias_pendientes = 0;
                    $expediente->id_ultimo_cent_asist = $usuario->id_centro_asistencial;
                    $expediente->es_abierto = true;
                    $expediente->id_create = $usuario->id;
                    $expediente->fecha_create = now();
                }
                $expediente->save();

                $codEstatus = ($expediente->cantidad_reposos >= 4) ? 3 : 1;
                $inicioReposo = Carbon::parse($request->inicio_reposo);
                $finReposo = Carbon::parse($request->fin_reposo);
                $diasIndemnizar = $inicioReposo->diffInDays($finReposo) + 1;

                $reposo = Reposo::create([
                    // 'id' => $maxId + 1, // <-- LÍNEA ELIMINADA
                    'id_expediente' => $expediente->id,
                    'cedula' => $cedula,
                    'id_empresa' => $idEmpresa,
                    'id_servicio' => $usuario->id_servicio,
                    'id_capitulo' => $request->id_capitulo,
                    'id_pat_general' => $request->id_pat_general,
                    'id_pat_especifica' => $request->id_pat_especifica,
                    'id_lugar' => $usuario->centroAsistencial->cod_tipo,
                    'cod_motivo' => $request->cod_motivo,
                    'inicio_reposo' => $request->inicio_reposo,
                    'fin_reposo' => $request->fin_reposo,
                    'reintegro' => $request->reintegro,
                    'debe_volver' => $request->debe_volver,
                    'convalidado' => $request->convalidado ?? 0,
                    'es_enfermedad' => 1,
                    'es_prenatal' => 0,
                    'es_postnatal' => 0,
                    'cod_estatus' => $codEstatus,
                    'dias_indemnizar' => $diasIndemnizar,
                    'id_create' => $usuario->id,
                    'fecha_create' => now(),
                    'id_cent_asist' => $usuario->id_centro_asistencial,
                    'observaciones' => strtoupper($request->observaciones),
                    'email_trabajador' => strtoupper($request->email_trabajador),
                    'tlf_habitacion' => $request->tlf_habitacion,
                    'tlf_oficina' => $request->tlf_oficina,
                    'tlf_movil' => $request->tlf_movil,
                    'email_jefe_inmediato' => strtoupper($request->email_jefe_inmediato),
                    'incapacidad_por' => strtoupper($request->incapacidad_por),
                    'posee_examenes' => $request->posee_examenes,
                    'sexo' => $sexoAsegurado,
                ]);

                $centroAsistencial = CentroAsistencial::where('id', $usuario->id_centro_asistencial)->first();
                $centroAsistencial->nro_reposo_1473 += 1;
                $centroAsistencial->save();

                $totalDiasIndemnizar = Reposo::where('cedula', $cedula)->sum('dias_indemnizar');
                $expediente->dias_acumulados = $totalDiasIndemnizar;
                $expediente->id_ultimo_reposo = $reposo->id;
                $expediente->save();

                $reposo->numero_ref_reposo = "{$reposo->id_cent_asist}{$reposo->id_servicio}{$reposo->id}";
                $reposo->save();

                $parametrosProcedimiento = [
                    'p_centro_asistencial' => $usuario->id_centro_asistencial,
                    'p_numero_relacion' => $reposo->numero_ref_reposo,
                    'p_fecha_elaboracion' => now()->format('d/m/Y'),
                    'p_id_empresa' => $idEmpresa,
                    'p_id_asegurado' => $idAsegurado,
                    'p_fecha_comienzo' => Carbon::parse($request->inicio_reposo)->format('d/m/Y'),
                    'p_tipo_concepto' => $request->cod_motivo,
                    'p_fecha_desde' => Carbon::parse($request->inicio_reposo)->format('d/m/Y'),
                    'p_fecha_hasta' => Carbon::parse($request->fin_reposo)->format('d/m/Y'),
                    'maternidad' => 'NO',
                    'p_fecha_prenatal' => null,
                    'p_fecha_postnatal' => null,
                    'p_id_usuario' => $usuario->id,
                    'p_fecha_transcripcion' => now()->format('d/m/Y'),
                ];

                $this->llamarProcedimientoIndemnizacion($parametrosProcedimiento);

                $cedulaPdf = $reposo->cedula;
                $formattedCedula = ltrim(substr($cedulaPdf, 1), '0');
                $prefix = substr($cedulaPdf, 0, 1);
                $prefix = ($prefix == '1') ? 'V' : (($prefix == '2') ? 'E' : '');
                $cedulaFormateada = $prefix . '-' . $formattedCedula;

                $data = [
                    'reposo' => $reposo,
                    'expediente' => $expediente,
                    'aseguradoEmpresa' => $aseguradoEmpresa,
                    'ciudadano' => $ciudadano,
                    'usuario' => $usuario,
                    'cedula_formateada' => $cedulaFormateada,
                    'fecha_elaboracion' => now()->format('d/m/Y'),
                    'email_empresa' => $emailEmpresa,
                ];

                $pdf = PDF::loadView('reposos.certificado_pdf', $data);
                $pdf->save(storage_path('app/public/app/assets/certificados/F-14-73_ENFERMEDAD_'.$reposo->id.'.pdf'));
            });

            return redirect('/inicio')->with('success', 'Reposo registrado exitosamente!');

        } catch (\Exception $e) {
            Log::error('Error al registrar el Reposo: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Error al registrar el Reposo. Inténtalo nuevamente. Detalle: ' . $e->getMessage());
        }
    }

    private function llamarProcedimientoIndemnizacion(array $params)
    {
        $params['p_fecha_prenatal'] = $params['p_fecha_prenatal'] ?? '';
        $params['p_fecha_postnatal'] = $params['p_fecha_postnatal'] ?? '';

        $sql = "BEGIN REPOSO_INDEMNIZACIONES(:p_centro_asistencial, :p_numero_relacion, :p_fecha_elaboracion, :p_id_empresa, :p_id_asegurado, :p_fecha_comienzo, :p_tipo_concepto, :p_fecha_desde, :p_fecha_hasta, :maternidad, :p_fecha_prenatal, :p_fecha_postnatal, :p_id_usuario, :p_fecha_transcripcion); END;";

        try {
            DB::statement($sql, $params);
            Log::info('Procedimiento REPOSO_INDEMNIZACIONES ejecutado con éxito.');

        } catch (Throwable $e) {
            Log::error('Error al ejecutar el procedimiento REPOSO_INDEMNIZACIONES: ' . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            throw $e;
        }
    }
}

