<?php

namespace App\Http\Controllers;

use App\Models\CentroAsistencial;
use App\Models\Estado;
use App\Models\Expediente;
use App\Models\Prorroga;
use App\Models\Reposo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // public function index()
    // {
    //     // Obtener el mes actual
    //     $mesActual = Carbon::now()->format('Y-m');
        
    //     // Datos para el gráfico de pie (reposos por tipo del mes actual)
    //     $enfermedadCount = Reposo::where('cod_motivo', 1)
    //                             ->where('inicio_reposo', 'like', $mesActual . '%')
    //                             ->count();

    //     $enfermedadProfesionalCount = Reposo::where('cod_motivo', 4)
    //                             ->where('inicio_reposo', 'like', $mesActual . '%')
    //                             ->count();

    //     $accidenteCount = Reposo::where('cod_motivo', 3)
    //                             ->where('inicio_reposo', 'like', $mesActual . '%')
    //                             ->count();

    //     $accidenteLaboralCount = Reposo::where('cod_motivo', 5)
    //                             ->where('inicio_reposo', 'like', $mesActual . '%')
    //                             ->count();
        
    //     $prenatalCount = Reposo::where('es_prenatal', true)
    //                          ->where('inicio_reposo', 'like', $mesActual . '%')
    //                          ->count();
        
    //     $postnatalCount = Reposo::where('es_postnatal', true)
    //                           ->where('inicio_reposo', 'like', $mesActual . '%')
    //                           ->count();
        
    //     // Contar prórrogas del mes actual usando FECHA_CREATE
    //     $prorrogasCount = Prorroga::where('FECHA_CREATE', 'like', $mesActual . '%')
    //                             ->count();

    //     // Reposos por Estado
    //     $estados = Estado::with(['centrosAsistenciales.reposos' => function ($query) use ($mesActual) {
    //         $query->where('inicio_reposo', 'like', $mesActual . '%');
    //     }])->get();

    //     $labels = [];
    //     $data = [];

    //     foreach ($estados as $estado) {
    //         $labels[] = $estado->nombre;
    //         $repososCount = 0;
    //         foreach ($estado->centrosAsistenciales as $centro) {
    //             $repososCount += $centro->reposos->count();
    //         }
    //         $data[] = $repososCount;
    //     }

    //     $expedientesPorMes = Expediente::selectRaw('TO_CHAR(fecha_create, \'YYYY-MM\') as mes, COUNT(*) as cantidad')
    //         ->groupBy(DB::raw('TO_CHAR(fecha_create, \'YYYY-MM\')'))
    //         ->orderBy(DB::raw('TO_CHAR(fecha_create, \'YYYY-MM\')'))
    //         ->get();

    //     $expedientesLabels = [];
    //     $expedientesData = [];

    //     foreach ($expedientesPorMes as $expediente) {
    //         $expedientesLabels[] = $expediente->mes;
    //         $expedientesData[] = $expediente->cantidad;
    //     }

    //     return view('dashboard', compact(
    //         'enfermedadCount',
    //         'enfermedadProfesionalCount',
    //         'accidenteCount',
    //         'accidenteLaboralCount',
    //         'prenatalCount',
    //         'postnatalCount',
    //         'prorrogasCount',
    //         'labels',
    //         'data',
    //         'expedientesLabels',
    //         'expedientesData'
    //     ));
    // }

    public function index()
    {
        return view('dashboard');
    }
}
