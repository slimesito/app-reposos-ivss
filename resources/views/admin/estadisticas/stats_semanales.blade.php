@extends('layout.layout')

@section('title', 'Estadísticas Semanales')

@section('content')

    <div class="col-sm-12 col-xl-12">
        @include('layout.alerts.success-message')
        @include('layout.alerts.reposos-success')
        @include('layout.alerts.error-message')

        <div class="bg-secondary rounded h-100 p-4">
            <h1 class="display-6 mb-0">Estadísticas Semanales - {{ $rangoSemana }}</h1>
        </div>
    </div>

    <div class="col-sm-12 col-xl-12">
        <!-- Gráfico de pie (incluye tipos de reposo y sexo) -->
        <div class="bg-secondary rounded h-100 p-4">
            <h6 class="mb-4">Distribución de Reposos</h6>
            <canvas id="pie-chart"></canvas>
        </div>
    </div>

    <div class="col-sm-12 col-xl-12">
        <!-- Gráfico de barras (reposos por estado) -->
        <div class="bg-secondary rounded h-100 p-4">
            <h6 class="mb-4">Reposos por Estado</h6>
            <canvas id="bar-chart"></canvas>
        </div>
    </div>

    <script>
        // Configuración del gráfico de pie
        var ctxPie = document.getElementById('pie-chart').getContext('2d');
        var pieChart = new Chart(ctxPie, {
            type: "pie",
            data: {
                labels: [
                    "Enfermedad Común", 
                    "Enfermedad Profesional", 
                    "Accidente Común", 
                    "Accidente Laboral", 
                    "Prenatal", 
                    "Postnatal", 
                    "Prórrogas", 
                    "Convalidados", 
                    "Otorgados",
                    "Mujeres (F)",
                    "Hombres (M)"
                ],
                datasets: [{
                    data: [
                        {{ $enfermedadCount }},
                        {{ $enfermedadProfesionalCount }},
                        {{ $accidenteCount }},
                        {{ $accidenteLaboralCount }},
                        {{ $prenatalCount }},
                        {{ $postnatalCount }},
                        {{ $prorrogasCount }},
                        {{ $convalidadosCount }},
                        {{ $otorgadosCount }},
                        {{ $femeninoCount }},
                        {{ $masculinoCount }}
                    ],
                    backgroundColor: [
                        "rgba(235, 22, 22, .9)",
                        "rgba(235, 22, 22, .8)", 
                        "rgba(235, 22, 22, .7)",
                        "rgba(235, 22, 22, .6)",
                        "rgba(235, 22, 22, .5)",
                        "rgba(235, 22, 22, .4)",
                        "rgba(235, 22, 22, .3)",
                        "rgba(235, 22, 22, .2)",
                        "rgba(235, 22, 22, .1)",
                        "rgba(255, 99, 132, 0.9)", // Rosa para mujeres
                        "rgba(54, 162, 235, 0.9)"  // Azul para hombres
                    ],
                    borderColor: "rgba(255, 255, 255, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.raw || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Configuración del gráfico de barras
        var ctxBar = document.getElementById('bar-chart').getContext('2d');
        var barChart = new Chart(ctxBar, {
            type: "bar",
            data: {
                labels: @json($labels),
                datasets: [{
                    label: "Reposos por Estado",
                    data: @json($data),
                    backgroundColor: "rgba(235, 22, 22, .7)",
                    borderColor: "rgba(235, 22, 22, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

@endsection