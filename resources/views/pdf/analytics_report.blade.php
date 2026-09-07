<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Estadístico - {{ $schoolYear->name ?? 'SGA' }}</title>
    <style>
        @page { margin: 25px 30px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f3242;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #26465c;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .school-name {
            font-size: 14px;
            font-weight: bold;
            color: #26465c;
            text-transform: uppercase;
        }
        .report-title {
            font-size: 12px;
            font-weight: bold;
            color: #336b87;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .meta-info {
            font-size: 9px;
            color: #5d9bb4;
            margin-top: 3px;
        }
        
        /* KPI Cards */
        .kpi-container {
            width: 100%;
            margin-bottom: 15px;
        }
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
        }
        .kpi-cell {
            background: #f0f6f9;
            border: 1px solid #bdd7e3;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
            width: 25%;
        }
        .kpi-val {
            font-size: 16px;
            font-weight: bold;
            color: #26465c;
            margin-bottom: 2px;
        }
        .kpi-lbl {
            font-size: 8px;
            text-transform: uppercase;
            color: #5d9bb4;
            font-weight: bold;
        }

        /* Section Title */
        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #26465c;
            text-transform: uppercase;
            margin: 14px 0 6px;
            border-bottom: 1px solid #dcebf2;
            padding-bottom: 3px;
        }

        /* Data Tables */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #dcebf2;
            padding: 5px 6px;
            text-align: center;
        }
        table.data-table th {
            background: #26465c;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        table.data-table tr:nth-child(even) {
            background: #f8fafc;
        }
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
        .badge-pass { color: #047857; font-weight: bold; }
        .badge-fail { color: #b91c1c; font-weight: bold; }

        .footer {
            margin-top: 30px;
            width: 100%;
        }
        .sig-cell {
            text-align: center;
            width: 50%;
            padding-top: 40px;
        }
        .sig-line {
            width: 180px;
            border-top: 1px solid #7f8c8d;
            margin: 0 auto 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td style="width: 75%;">
                    <div class="school-name">{{ $school['name'] }}</div>
                    <div class="report-title">Informe Estadístico de Rendimiento Académico</div>
                    <div class="meta-info">
                        Año Escolar: <strong>{{ $schoolYear->name ?? '—' }}</strong>
                        @if($lapse) | Lapso: <strong>{{ $lapse->name }}</strong> @endif
                        | Emitido el: {{ $generatedAt }}
                    </div>
                </td>
                <td style="width: 25%; text-align: right; vertical-align: top;">
                    <div style="font-size: 8px; color: #7f8c8d;">SGA Académico</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- KPIs -->
    <div class="kpi-container">
        <table class="kpi-table">
            <tr>
                <td class="kpi-cell">
                    <div class="kpi-val">{{ $metrics['kpis']['total_students'] }}</div>
                    <div class="kpi-lbl">Estudiantes Evaluados</div>
                </td>
                <td class="kpi-cell">
                    <div class="kpi-val">{{ number_format($metrics['kpis']['overall_average'], 2) }} / 20</div>
                    <div class="kpi-lbl">Promedio General</div>
                </td>
                <td class="kpi-cell">
                    <div class="kpi-val badge-pass">{{ $metrics['kpis']['pass_rate'] }}%</div>
                    <div class="kpi-lbl">Tasa de Aprobación ({{ $metrics['kpis']['passed_count'] }})</div>
                </td>
                <td class="kpi-cell">
                    <div class="kpi-val badge-fail">{{ $metrics['kpis']['fail_rate'] }}%</div>
                    <div class="kpi-lbl">Tasa de Reprobación ({{ $metrics['kpis']['failed_count'] }})</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Distribución de notas -->
    <div class="section-title">Distribución Pedagógica de Calificaciones</div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-left">Rango de Calificación</th>
                <th>Criterio Pedagógico</th>
                <th>Calificaciones</th>
                <th>Porcentaje</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-left">01 a 09 puntos</td>
                <td>Deficiente (Reprobado)</td>
                <td>{{ $metrics['histogram']['counts'][0] }}</td>
                <td class="badge-fail">{{ $metrics['histogram']['percentages'][0] }}%</td>
            </tr>
            <tr>
                <td class="text-left">10 a 13 puntos</td>
                <td>Regular (Aprobado mínimo)</td>
                <td>{{ $metrics['histogram']['counts'][1] }}</td>
                <td>{{ $metrics['histogram']['percentages'][1] }}%</td>
            </tr>
            <tr>
                <td class="text-left">14 a 17 puntos</td>
                <td>Bueno</td>
                <td>{{ $metrics['histogram']['counts'][2] }}</td>
                <td>{{ $metrics['histogram']['percentages'][2] }}%</td>
            </tr>
            <tr>
                <td class="text-left">18 a 20 puntos</td>
                <td>Excelente (Sobresaliente)</td>
                <td>{{ $metrics['histogram']['counts'][3] }}</td>
                <td class="badge-pass">{{ $metrics['histogram']['percentages'][3] }}%</td>
            </tr>
        </tbody>
    </table>

    <!-- Rendimiento por Materia -->
    <div class="section-title">Rendimiento por Asignatura</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;">Código</th>
                <th class="text-left" style="width: 38%;">Asignatura</th>
                <th style="width: 12%;">Evaluados</th>
                <th style="width: 12%;">% Aprobados</th>
                <th style="width: 12%;">% Aplazados</th>
                <th style="width: 14%;">Promedio</th>
            </tr>
        </thead>
        <tbody>
            @forelse($metrics['subjects'] as $sub)
                <tr>
                    <td>{{ $sub['code'] }}</td>
                    <td class="text-left">{{ $sub['name'] }}</td>
                    <td>{{ $sub['evaluated'] }}</td>
                    <td class="badge-pass">{{ $sub['pass_rate'] }}%</td>
                    <td class="{{ $sub['fail_rate'] > 20 ? 'badge-fail' : '' }}">{{ $sub['fail_rate'] }}%</td>
                    <td style="font-weight: bold;">{{ number_format($sub['average'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="padding: 15px; color: #7f8c8d;">No hay calificaciones registradas en este período.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Firmas -->
    <table class="footer">
        <tr>
            <td class="sig-cell">
                <div class="sig-line"></div>
                <strong>Dirección del Plantel</strong>
            </td>
            <td class="sig-cell">
                <div class="sig-line"></div>
                <strong>Coordinación de Control de Estudios</strong>
            </td>
        </tr>
    </table>
</body>
</html>
