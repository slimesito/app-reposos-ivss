<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CentroAsistencial;
use App\Models\Estado;
use App\Models\Expediente;
use App\Models\Prorroga;
use App\Models\Reposo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class EstadisticasController extends Controller
{
    public function estadisticasAnuales()
    {
        $currentYear = Carbon::now()->year;

        // Estadísticas principales
        $reposos = Reposo::whereYear('inicio_reposo', $currentYear)
            ->selectRaw('
                SUM(CASE WHEN cod_motivo = 1 THEN 1 ELSE 0 END) as enfermedad,
                SUM(CASE WHEN cod_motivo = 4 THEN 1 ELSE 0 END) as enfermedad_profesional,
                SUM(CASE WHEN cod_motivo = 3 THEN 1 ELSE 0 END) as accidente,
                SUM(CASE WHEN cod_motivo = 5 THEN 1 ELSE 0 END) as accidente_laboral,
                SUM(CASE WHEN es_prenatal = 1 THEN 1 ELSE 0 END) as prenatal,
                SUM(CASE WHEN es_postnatal = 1 THEN 1 ELSE 0 END) as postnatal,
                SUM(CASE WHEN convalidado = 1 THEN 1 ELSE 0 END) as convalidados,
                SUM(CASE WHEN convalidado = 0 THEN 1 ELSE 0 END) as otorgados,
                SUM(CASE WHEN UPPER(sexo) = \'F\' THEN 1 ELSE 0 END) as femenino,
                SUM(CASE WHEN UPPER(sexo) = \'M\' THEN 1 ELSE 0 END) as masculino
            ')->first();

        $prorrogasCount = Prorroga::whereYear('FECHA_CREATE', $currentYear)->count();

        // Estadísticas por estado
        $estados = Estado::with(['centrosAsistenciales.reposos' => function ($query) use ($currentYear) {
            $query->whereYear('inicio_reposo', $currentYear);
        }])->get();

        $labels = [];
        $data = [];

        foreach ($estados as $estado) {
            $labels[] = $estado->nombre;
            $repososCount = 0;
            foreach ($estado->centrosAsistenciales as $centro) {
                $repososCount += $centro->reposos->count();
            }
            $data[] = $repososCount;
        }

        return view('admin.estadisticas.stats_anuales', [
            'enfermedadCount' => $reposos->enfermedad ?? 0,
            'enfermedadProfesionalCount' => $reposos->enfermedad_profesional ?? 0,
            'accidenteCount' => $reposos->accidente ?? 0,
            'accidenteLaboralCount' => $reposos->accidente_laboral ?? 0,
            'prenatalCount' => $reposos->prenatal ?? 0,
            'postnatalCount' => $reposos->postnatal ?? 0,
            'prorrogasCount' => $prorrogasCount,
            'convalidadosCount' => $reposos->convalidados ?? 0,
            'otorgadosCount' => $reposos->otorgados ?? 0,
            'femeninoCount' => $reposos->femenino ?? 0,
            'masculinoCount' => $reposos->masculino ?? 0,
            'thisYear' => Carbon::now()->locale('es')->isoFormat('YYYY'),
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    public function estadisticasMensuales()
    {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        $mesActual = Carbon::now()->locale('es')->isoFormat('MMMM YYYY');

        // Estadísticas mensuales
        $reposos = Reposo::whereYear('inicio_reposo', $currentYear)
            ->whereMonth('inicio_reposo', $currentMonth)
            ->selectRaw('
                SUM(CASE WHEN cod_motivo = 1 THEN 1 ELSE 0 END) as enfermedad,
                SUM(CASE WHEN cod_motivo = 4 THEN 1 ELSE 0 END) as enfermedad_profesional,
                SUM(CASE WHEN cod_motivo = 3 THEN 1 ELSE 0 END) as accidente,
                SUM(CASE WHEN cod_motivo = 5 THEN 1 ELSE 0 END) as accidente_laboral,
                SUM(CASE WHEN es_prenatal = 1 THEN 1 ELSE 0 END) as prenatal,
                SUM(CASE WHEN es_postnatal = 1 THEN 1 ELSE 0 END) as postnatal,
                SUM(CASE WHEN convalidado = 1 THEN 1 ELSE 0 END) as convalidados,
                SUM(CASE WHEN convalidado = 0 THEN 1 ELSE 0 END) as otorgados,
                SUM(CASE WHEN UPPER(sexo) = \'F\' THEN 1 ELSE 0 END) as femenino,
                SUM(CASE WHEN UPPER(sexo) = \'M\' THEN 1 ELSE 0 END) as masculino
            ')->first();

        $prorrogasCount = Prorroga::whereYear('FECHA_CREATE', $currentYear)
            ->whereMonth('FECHA_CREATE', $currentMonth)
            ->count();

        // Estadísticas por estado
        $estados = Estado::with(['centrosAsistenciales.reposos' => function ($query) use ($currentYear, $currentMonth) {
            $query->whereYear('inicio_reposo', $currentYear)
                  ->whereMonth('inicio_reposo', $currentMonth);
        }])->get();

        $labels = [];
        $data = [];

        foreach ($estados as $estado) {
            $labels[] = $estado->nombre;
            $repososCount = 0;
            foreach ($estado->centrosAsistenciales as $centro) {
                $repososCount += $centro->reposos->count();
            }
            $data[] = $repososCount;
        }

        return view('admin.estadisticas.stats_mensuales', [
            'enfermedadCount' => $reposos->enfermedad ?? 0,
            'enfermedadProfesionalCount' => $reposos->enfermedad_profesional ?? 0,
            'accidenteCount' => $reposos->accidente ?? 0,
            'accidenteLaboralCount' => $reposos->accidente_laboral ?? 0,
            'prenatalCount' => $reposos->prenatal ?? 0,
            'postnatalCount' => $reposos->postnatal ?? 0,
            'prorrogasCount' => $prorrogasCount,
            'convalidadosCount' => $reposos->convalidados ?? 0,
            'otorgadosCount' => $reposos->otorgados ?? 0,
            'femeninoCount' => $reposos->femenino ?? 0,
            'masculinoCount' => $reposos->masculino ?? 0,
            'mesActual' => $mesActual,
            'thisYear' => $currentYear,
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    public function estadisticasSemanales()
    {
        $inicioSemana = Carbon::now()->startOfWeek()->format('Y-m-d');
        $finSemana = Carbon::now()->endOfWeek()->format('Y-m-d');
        $currentYear = Carbon::now()->year;
        $rangoSemana = Carbon::now()->startOfWeek()->format('d/m/Y').' - '.Carbon::now()->endOfWeek()->format('d/m/Y');

        // Estadísticas semanales
        $reposos = Reposo::whereBetween('inicio_reposo', [$inicioSemana, $finSemana])
            ->selectRaw('
                SUM(CASE WHEN cod_motivo = 1 THEN 1 ELSE 0 END) as enfermedad,
                SUM(CASE WHEN cod_motivo = 4 THEN 1 ELSE 0 END) as enfermedad_profesional,
                SUM(CASE WHEN cod_motivo = 3 THEN 1 ELSE 0 END) as accidente,
                SUM(CASE WHEN cod_motivo = 5 THEN 1 ELSE 0 END) as accidente_laboral,
                SUM(CASE WHEN es_prenatal = 1 THEN 1 ELSE 0 END) as prenatal,
                SUM(CASE WHEN es_postnatal = 1 THEN 1 ELSE 0 END) as postnatal,
                SUM(CASE WHEN convalidado = 1 THEN 1 ELSE 0 END) as convalidados,
                SUM(CASE WHEN convalidado = 0 THEN 1 ELSE 0 END) as otorgados,
                SUM(CASE WHEN UPPER(sexo) = \'F\' THEN 1 ELSE 0 END) as femenino,
                SUM(CASE WHEN UPPER(sexo) = \'M\' THEN 1 ELSE 0 END) as masculino
            ')->first();

        $prorrogasCount = Prorroga::whereBetween('FECHA_CREATE', [$inicioSemana, $finSemana])->count();

        // Estadísticas por estado
        $estados = Estado::with(['centrosAsistenciales.reposos' => function ($query) use ($inicioSemana, $finSemana) {
            $query->whereBetween('inicio_reposo', [$inicioSemana, $finSemana]);
        }])->get();

        $labels = [];
        $data = [];

        foreach ($estados as $estado) {
            $labels[] = $estado->nombre;
            $repososCount = 0;
            foreach ($estado->centrosAsistenciales as $centro) {
                $repososCount += $centro->reposos->count();
            }
            $data[] = $repososCount;
        }

        return view('admin.estadisticas.stats_semanales', [
            'enfermedadCount' => $reposos->enfermedad ?? 0,
            'enfermedadProfesionalCount' => $reposos->enfermedad_profesional ?? 0,
            'accidenteCount' => $reposos->accidente ?? 0,
            'accidenteLaboralCount' => $reposos->accidente_laboral ?? 0,
            'prenatalCount' => $reposos->prenatal ?? 0,
            'postnatalCount' => $reposos->postnatal ?? 0,
            'prorrogasCount' => $prorrogasCount,
            'convalidadosCount' => $reposos->convalidados ?? 0,
            'otorgadosCount' => $reposos->otorgados ?? 0,
            'femeninoCount' => $reposos->femenino ?? 0,
            'masculinoCount' => $reposos->masculino ?? 0,
            'rangoSemana' => $rangoSemana,
            'thisYear' => $currentYear,
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    public function estadisticasDiarias()
    {
        $fechaActual = Carbon::now()->format('Y-m-d');
        $currentYear = Carbon::now()->year;
        $fechaFormateada = Carbon::now()->isoFormat('LL'); // Formato: "20 de mayo de 2024"

        // Estadísticas diarias
        $reposos = Reposo::whereDate('inicio_reposo', $fechaActual)
            ->selectRaw('
                SUM(CASE WHEN cod_motivo = 1 THEN 1 ELSE 0 END) as enfermedad,
                SUM(CASE WHEN cod_motivo = 4 THEN 1 ELSE 0 END) as enfermedad_profesional,
                SUM(CASE WHEN cod_motivo = 3 THEN 1 ELSE 0 END) as accidente,
                SUM(CASE WHEN cod_motivo = 5 THEN 1 ELSE 0 END) as accidente_laboral,
                SUM(CASE WHEN es_prenatal = 1 THEN 1 ELSE 0 END) as prenatal,
                SUM(CASE WHEN es_postnatal = 1 THEN 1 ELSE 0 END) as postnatal,
                SUM(CASE WHEN convalidado = 1 THEN 1 ELSE 0 END) as convalidados,
                SUM(CASE WHEN convalidado = 0 THEN 1 ELSE 0 END) as otorgados,
                SUM(CASE WHEN UPPER(sexo) = \'F\' THEN 1 ELSE 0 END) as femenino,
                SUM(CASE WHEN UPPER(sexo) = \'M\' THEN 1 ELSE 0 END) as masculino
            ')->first();

        $prorrogasCount = Prorroga::whereDate('FECHA_CREATE', $fechaActual)->count();

        // Estadísticas por estado
        $estados = Estado::with(['centrosAsistenciales.reposos' => function ($query) use ($fechaActual) {
            $query->whereDate('inicio_reposo', $fechaActual);
        }])->get();

        $labels = [];
        $data = [];

        foreach ($estados as $estado) {
            $labels[] = $estado->nombre;
            $repososCount = 0;
            foreach ($estado->centrosAsistenciales as $centro) {
                $repososCount += $centro->reposos->count();
            }
            $data[] = $repososCount;
        }

        return view('admin.estadisticas.stats_diarias', [
            'enfermedadCount' => $reposos->enfermedad ?? 0,
            'enfermedadProfesionalCount' => $reposos->enfermedad_profesional ?? 0,
            'accidenteCount' => $reposos->accidente ?? 0,
            'accidenteLaboralCount' => $reposos->accidente_laboral ?? 0,
            'prenatalCount' => $reposos->prenatal ?? 0,
            'postnatalCount' => $reposos->postnatal ?? 0,
            'prorrogasCount' => $prorrogasCount,
            'convalidadosCount' => $reposos->convalidados ?? 0,
            'otorgadosCount' => $reposos->otorgados ?? 0,
            'femeninoCount' => $reposos->femenino ?? 0,
            'masculinoCount' => $reposos->masculino ?? 0,
            'fechaActual' => $fechaFormateada,
            'thisYear' => $currentYear,
            'labels' => $labels,
            'data' => $data,
        ]);
    }
}
