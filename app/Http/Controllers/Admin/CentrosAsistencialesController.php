<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\StringHelpers;
use App\Http\Controllers\Controller;
use App\Models\CentroAsistencial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CentrosAsistencialesController extends Controller
{
    public function gestionCentroAsistencialView()
    {
        $centrosAsistenciales = CentroAsistencial::orderBy('cod_centro')->paginate(20);

        return view('admin.centros_asistenciales.gestion_centros_asistenciales', ['centrosAsistenciales' => $centrosAsistenciales]);
    }

    public function buscadorCentroAsistencial(Request $request)
    {
        $query = StringHelpers::strtoupper_searchCentrosAsistenciales($request->input('centroAsistencialQuery'));

        $centrosAsistenciales = CentroAsistencial::where('nombre', 'LIKE', '%' . $query . '%')
            ->paginate(10)
            ->appends(['centroAsistencialQuery' => $request->input('centroAsistencialQuery')]);

        return view('admin.centros_asistenciales.resultados_busqueda', compact('centrosAsistenciales'));
    }

    public function createCentroAsistencialView()
    {
        return view('admin.centros_asistenciales.nuevo_centro_asistencial');
    }

    public function createCentroAsistencial(Request $request)
    {
        // Depuración inicial de la solicitud
        // dd($request->all()); // Verifica los datos recibidos

        try {
            // Validación de datos
            $validator = Validator::make($request->all(), [
                'cod_centro' => 'required|integer|unique:CENTROS_ASISTENCIALES,cod_centro',
                'nombre' => 'required|string|max:250|unique:CENTROS_ASISTENCIALES,nombre',
                'cod_estado' => 'required|integer|exists:ESTADOS,id',
                'es_hospital' => 'required|in:0,1',
                'cod_tipo' => 'required|integer',
                'rango_ip' => 'nullable|string|max:11',
            ]);

            // if ($validator->fails()) {
            //     dd($validator->errors()->all()); // Depura errores de validación
            //     return redirect()->back()
            //         ->withErrors($validator)
            //         ->withInput();
            // }

            // Obtener ID usando secuencia de Oracle
            $sequenceName = 'CENTROS_ASISTENCIALES_ID_SEQ';
            $idResult = DB::selectOne("SELECT $sequenceName.NEXTVAL AS next_id FROM DUAL");
            // dd($idResult); // Verifica resultado de la secuencia

            $id = $idResult->next_id;

            // Depura usuario autenticado
            $user = Auth::user();
            // dd($user); // Verifica usuario obtenido

            // Crear registro
            $centro = new CentroAsistencial();
            $centro->id = $id;
            $centro->cod_centro = $request->cod_centro;
            $centro->nombre = StringHelpers::strtoupper_createCentroAsistencial($request->nombre);
            $centro->cod_estado = $request->cod_estado;
            $centro->es_hospital = $request->es_hospital;
            $centro->cod_tipo = $request->cod_tipo;
            $centro->nro_reposo_1473 = 0; // Asignar valor por defecto
            $centro->rango_ip = $request->rango_ip;
            $centro->activo = 1; // Asignar valor por defecto
            $centro->id_create = $user->id;
            $centro->fecha_create = now();

            // Depura objeto antes de guardar
            // dd($centro->toArray()); // Verifica datos del modelo

            $centro->save();

            return redirect()->route('gestion.centro-asistencial.view')
                ->with('success', 'Centro creado exitosamente!');

        } catch (\Exception $e) {
            // Depuración detallada del error
            // dd([
            //     'message' => $e->getMessage(),
            //     'file' => $e->getFile(),
            //     'line' => $e->getLine(),
            //     'trace' => $e->getTraceAsString()
            // ]);
        }
    }

    public function editarCentroAsistencialView($id)
    {
        $centroAsistencial = CentroAsistencial::findOrFail($id);
        return view('admin.centros_asistenciales.editar_centros_asistenciales', compact('centroAsistencial'));
    }

    public function updateCentroAsistencial(Request $request, $id)
    {
        $centroAsistencial = CentroAsistencial::findOrFail($id);

        // Regla de validación base
        $rules = [
            'cod_estado' => 'required|numeric|max:19',
            'es_hospital' => 'required|boolean',
            'cod_tipo' => 'required|numeric|max:19',
            'rango_ip' => 'required|max:11',
            'activo' => 'required|boolean',
        ];

        // Validación condicional para cod_centro
        if ($request->input('cod_centro') !== $centroAsistencial->cod_centro) {
            $rules['cod_centro'] = 'required|numeric|unique:centros_asistenciales,cod_centro';
        }

        // Validación condicional para nombre
        if (StringHelpers::strtoupper_updateCentroAsistencial($request->input('nombre')) !== $centroAsistencial->nombre) {
            $rules['nombre'] = 'required|max:250|unique:centros_asistenciales,nombre';
        }

        // Validar la solicitud
        $request->validate($rules);

        // Actualizar los campos
        if ($request->has('cod_centro')) {
            $centroAsistencial->cod_centro = $request->input('cod_centro');
        }
        if ($request->has('nombre')) {
            $centroAsistencial->nombre = StringHelpers::strtoupper_updateCentroAsistencial($request->input('nombre'));
        }
        $centroAsistencial->cod_estado = $request->input('cod_estado');
        $centroAsistencial->es_hospital = $request->input('es_hospital');
        $centroAsistencial->cod_tipo = $request->input('cod_tipo');
        $centroAsistencial->rango_ip = $request->input('rango_ip');
        $centroAsistencial->activo = $request->input('activo');
        $centroAsistencial->id_update = auth()->user()->id;
        $centroAsistencial->fecha_update = now();

        // Guardar los cambios
        $centroAsistencial->save();

        return redirect('/gestion_centros_asistenciales')->with('success', 'Centro Asistencial actualizado correctamente.');
    }

    public function destroyCentroAsistencial($id)
    {
        $patologiaEspecifica = CentroAsistencial::findOrFail($id);
        $patologiaEspecifica->delete();

        return redirect()->back()->with('success', 'Centro Asistencial eliminado correctamente');
    }
}
