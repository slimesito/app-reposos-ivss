<?php

namespace App\Http\Controllers\Reposos;

use App\Http\Controllers\Controller;
use App\Models\AseguradoEmpresa;
use App\Models\Capitulo;
use App\Models\CentroAsistencial;
use App\Models\Ciudadano;
use App\Models\Empresa;
use App\Models\Expediente;
use App\Models\Forma_14144;
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

class RepososMaternidadController extends Controller
{
    public function validarCedulaReposoMaternidadView()
    {
        return view('reposos.validar_cedula_maternidad');
    }

    public function validarCedulaReposoMaternidad(Request $request)
    {
        $cedula = $request->input('cedula');
        $nacionalidad = $request->input('nacionalidad');

        // Formats the ID number based on nationality and adds zeros
        if ($nacionalidad == 1) {
            // Venezuelan
            $formattedCedula = '1' . str_pad($cedula, 9, '0', STR_PAD_LEFT);
        } else {
            // Foreigner
            $formattedCedula = '2' . str_pad($cedula, 9, '0', STR_PAD_LEFT);
        }

        // Queries the database to verify the insured person
        $asegurado = AseguradoEmpresa::where('id_asegurado', $formattedCedula)->first();

        if ($asegurado) {
            // Adjusts the ID format to search in the Ciudadano table
            $prefijo = substr($formattedCedula, 0, 1) === '1' ? 'V' : (substr($formattedCedula, 0, 1) === '2' ? 'E' : null);
            $cedulaAjustada = $prefijo ? $prefijo . substr($formattedCedula, 1) : $formattedCedula;

            // Searches for the citizen in the Ciudadano table
            $ciudadano = Ciudadano::where('id_ciudadano', $cedulaAjustada)->first();

            // Validates if the citizen is female
            if ($ciudadano && $ciudadano->sexo === 'F') {
                // Stores the ID in the session
                session(['cedula' => $formattedCedula]);
                return redirect()->route('nuevo.reposo.maternidad.view');
            } else {
                // The ID does not correspond to a female citizen
                return redirect()->back()->withErrors(['cedula' => 'La cédula no corresponde a una ciudadana femenina.']);
            }
        } else {
            // No insured person found with that ID
            return redirect()->back()->withErrors(['cedula' => 'No se encontró ningún asegurado con esa cédula.']);
        }
    }

    public function nuevoReposoMaternidadView()
    {
        $servicios = Servicio::all();
        $capitulos = Capitulo::where('id', 16)->get();
        $patologiasGenerales = PatologiaGeneral::where('capitulo_id', 16)->get();
        $patologiasEspecificas = PatologiaEspecifica::where('capitulo_id', 16)->get();
        $lugares = Lugar::all();
        $motivos = Motivo::all();

        return view('reposos.nuevo_reposo_maternidad', compact('servicios', 'capitulos', 'patologiasGenerales', 'patologiasEspecificas', 'lugares', 'motivos'));
    }

    public function getPatologiasGenerales($id)
    {
        $patologiasGenerales = PatologiaGeneral::where('capitulo_id', $id)->get();

        // Ensures JSON is returned
        return response()->json($patologiasGenerales);
    }

    public function createReposoMaternidad(Request $request)
    {
        // Data validation
        $request->validate([
            'id_pat_general' => 'required|numeric',
            'id_pat_especifica' => 'nullable|numeric',
            'inicio_reposo' => 'required|date',
            'fin_reposo' => 'required|date',
            'reintegro' => 'required|date',
            'debe_volver' => 'required|boolean',
            'observaciones' => 'nullable|string',
            'email_trabajador' => 'required|email',
            'es_prenatal' => 'required|boolean', // Added for maternity logic
            'es_postnatal' => 'required|boolean', // Added for maternity logic
        ]);

        try {
            $result = DB::transaction(function () use ($request) {
                $cedula = session('cedula');
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

                if (!$aseguradoEmpresa || !$aseguradoEmpresa->id_empresa) {
                    throw new \Exception('No se encontró la empresa asociada al asegurado.');
                }

                $idAsegurado = $aseguradoEmpresa->id_asegurado;
                $idEmpresa = $aseguradoEmpresa->id_empresa;

                // Finds or creates the associated file and updates reposos count if it exists
                $expediente = Expediente::firstOrNew(['cedula' => $cedula]);
                if ($expediente->exists) {
                    $expediente->cantidad_reposos += 1;
                    $expediente->id_update = auth()->user()->id;
                    $expediente->fecha_update = now();
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

                // Creates the reposo, letting the database assign the ID
                $reposo = Reposo::create([
                    'id_expediente' => $expediente->id,
                    'cedula' => $cedula,
                    'id_empresa' => $idEmpresa,
                    'id_servicio' => $usuario->id_servicio,
                    'id_capitulo' => 16, // Maternity Chapter
                    'id_pat_general' => $request->id_pat_general,
                    'id_pat_especifica' => $request->id_pat_especifica,
                    'id_lugar' => $usuario->centroAsistencial->cod_tipo,
                    'cod_motivo' => 2, // Maternity Motive
                    'inicio_reposo' => $request->inicio_reposo,
                    'fin_reposo' => $request->fin_reposo,
                    'reintegro' => $request->reintegro,
                    'debe_volver' => $request->debe_volver,
                    'convalidado' => $request->convalidado ?? 0,
                    'es_enfermedad' => 0,
                    'es_prenatal' => $request->es_prenatal,
                    'es_postnatal' => $request->es_postnatal,
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
                ]);

                // Updates the reposo with the generated reference number
                $reposo->numero_ref_reposo = "{$reposo->id_cent_asist}{$reposo->id_servicio}{$reposo->id}";
                $reposo->save();

                // Updates the expediente
                $expediente->dias_acumulados = Reposo::where('cedula', $cedula)->sum('dias_indemnizar');
                $expediente->id_ultimo_reposo = $reposo->id;
                $expediente->save();

                // Increments the counter in CentroAsistencial
                $centroAsistencial = CentroAsistencial::findOrFail($usuario->id_centro_asistencial);
                $centroAsistencial->nro_reposo_1473 += 1;
                $centroAsistencial->save();

                // *** START OF PROCEDURE CALL INTEGRATION ***
                $maternidadTipo = 'NO';
                if ($request->es_prenatal) {
                    $maternidadTipo = 'PRE';
                } elseif ($request->es_postnatal) {
                    $maternidadTipo = 'POS';
                }

                $parametrosProcedimiento = [
                    'p_centro_asistencial' => $usuario->id_centro_asistencial,
                    'p_numero_relacion' => $reposo->numero_ref_reposo,
                    'p_fecha_elaboracion' => now()->format('d/m/Y'),
                    'p_id_empresa' => $idEmpresa,
                    'p_id_asegurado' => $idAsegurado,
                    'p_fecha_comienzo' => Carbon::parse($request->inicio_reposo)->format('d/m/Y'),
                    'p_tipo_concepto' => 2, // Maternity Concept
                    'p_fecha_desde' => Carbon::parse($request->inicio_reposo)->format('d/m/Y'),
                    'p_fecha_hasta' => Carbon::parse($request->fin_reposo)->format('d/m/Y'),
                    'maternidad' => $maternidadTipo,
                    'p_fecha_prenatal' => $request->es_prenatal ? Carbon::parse($request->inicio_reposo)->format('d/m/Y') : null,
                    'p_fecha_postnatal' => $request->es_postnatal ? Carbon::parse($request->inicio_reposo)->format('d/m/Y') : null,
                    'p_id_usuario' => $usuario->id,
                    'p_fecha_transcripcion' => now()->format('d/m/Y'),
                ];

                $this->llamarProcedimientoIndemnizacion($parametrosProcedimiento);
                // *** END OF PROCEDURE CALL INTEGRATION ***

                // Formats the ID for the PDF
                $cedulaPdf = $reposo->cedula;
                $formattedCedula = ltrim(substr($cedulaPdf, 1), '0');
                $prefix = substr($cedulaPdf, 0, 1);
                $prefix = ($prefix == '1') ? 'V' : (($prefix == '2') ? 'E' : '');
                $cedulaFormateada = $prefix . '-' . $formattedCedula;

                // Generates PDF
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

                // Note: The view name might need to be adjusted (e.g., 'reposos.certificado_maternidad_pdf')
                $pdf = PDF::loadView('reposos.certificado_pdf', $data);
                // The filename is also adjusted for maternity
                $pdf->save(storage_path('app/public/app/assets/certificados/F-14-73_MATERNIDAD_'.$reposo->id.'.pdf'));
            });

            return redirect('/inicio')->with('success', 'Reposo de Maternidad registrado exitosamente!');

        } catch (\Exception $e) {
            Log::error('Error al registrar el Reposo de Maternidad: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Error al registrar el Reposo. Inténtalo nuevamente. Detalle: ' . $e->getMessage());
        }
    }

    /**
     * Executes the stored procedure REPOSO_INDEMNIZACIONES.
     * This method encapsulates the logic to be called internally.
     *
     * @param array $params The parameters for the procedure.
     * @throws \Throwable If a database error occurs.
     */
    private function llamarProcedimientoIndemnizacion(array $params)
    {
        // Oracle requires null parameters to be handled explicitly
        $params['p_fecha_prenatal'] = $params['p_fecha_prenatal'] ?? '';
        $params['p_fecha_postnatal'] = $params['p_fecha_postnatal'] ?? '';

        // Builds the call to the stored procedure in a PL/SQL block
        $sql = "BEGIN REPOSO_INDEMNIZACIONES(:p_centro_asistencial, :p_numero_relacion, :p_fecha_elaboracion, :p_id_empresa, :p_id_asegurado, :p_fecha_comienzo, :p_tipo_concepto, :p_fecha_desde, :p_fecha_hasta, :maternidad, :p_fecha_prenatal, :p_fecha_postnatal, :p_id_usuario, :p_fecha_transcripcion); END;";

        try {
            // Executes the procedure using DB::statement for anonymous blocks
            DB::statement($sql, $params);
            Log::info('Procedimiento REPOSO_INDEMNIZACIONES ejecutado con éxito para Maternidad.');

        } catch (Throwable $e) {
            // Logs the detailed error
            Log::error('Error al ejecutar el procedimiento REPOSO_INDEMNIZACIONES para Maternidad: ' . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);

            // Rethrows the exception so the main transaction can rollback
            throw $e;
        }
    }
}
