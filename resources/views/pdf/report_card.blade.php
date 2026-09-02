<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boletín Informativo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            margin: 5mm; /* Margen pequeño para asegurar que no se corte en impresoras, pero manteniendo espacio */
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #111;
            margin: 0;
            padding: 0;
        }

        .bulletin-wrapper {
            /* DomPDF ignora box-sizing a veces, sumando el padding al height */
            height: 120mm; 
            padding: 5mm 10mm; 
            position: relative;
            overflow: hidden;
        }

        .dotted-divider {
            border-bottom: 1px dashed #aaa;
            margin-bottom: 0;
        }

        /* ===== ENCABEZADO ===== */
        .header-table { width: 100%; border-collapse: collapse; border: 1px solid #000; }
        .header-table td { border: 1px solid #000; padding: 2px 4px; vertical-align: middle; }

        .title-row td {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            letter-spacing: 1px;
            padding: 4px;
            background: #f0f0f0;
        }

        .logo-cell { width: 60px; text-align: center; vertical-align: middle; }
        .logo-cell img { width: 50px; height: 50px; object-fit: contain; }
        .logo-placeholder { width: 50px; height: 50px; border: 1px solid #ccc; display: inline-block; }

        .label { font-size: 7px; font-weight: bold; text-transform: uppercase; color: #555; }
        .value { font-size: 9px; font-weight: bold; }
        .value-lg { font-size: 11px; font-weight: bold; }

        /* ===== TABLA PRINCIPAL ===== */
        .main-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .main-table th, .main-table td {
            border: 1px solid #555;
            padding: 2px 3px;
            text-align: center;
            vertical-align: middle;
        }

        .main-table thead th {
            background: #d4d4d4;
            font-weight: bold;
            font-size: 7px;
            text-transform: uppercase;
        }

        .group-header {
            background: #b8b8b8;
            font-weight: bold;
            font-size: 7.5px;
            text-transform: uppercase;
            padding: 3px;
        }

        .subject-col {
            text-align: left !important;
            padding-left: 5px !important;
            font-weight: bold;
            font-size: 8px;
            min-width: 130px;
        }

        /* Columnas de notas numéricas */
        .score-col { width: 28px; }
        .adj-col   { width: 22px; }
        .def-col   { width: 28px; font-weight: bold; }
        .abs-col   { width: 22px; }
        .final-col { width: 32px; font-weight: bold; font-size: 10px; }
        .rev-col   { width: 32px; }
        .pend-col  { width: 80px; text-align: left !important; padding-left: 3px !important; }

        .row-average { background: #e8e8e8; font-weight: bold; }
        .row-qualitative { background: #fafafa; font-style: italic; }

        .failed  { color: #cc0000; font-weight: bold; }
        .pending-mark { color: #cc0000; font-size: 7px; }
        .approved { color: #006600; }

        /* ===== PIE ===== */
        .signatures-row {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8mm; /* Espacio para que firmen */
        }
        .signatures-row td { 
            text-align: center; 
            width: 50%;
            padding: 0 10px;
        }
        .sig-line { 
            border-top: 1px solid #333; 
            padding-top: 3px; 
            margin: 0 auto; 
            width: 70%; 
            font-size: 8px; 
            font-weight: bold; 
            color: #111;
        }
        .footer-text {
            text-align: center;
            font-size: 7px;
            color: #888;
            margin-top: 6mm;
        }

    </style>
</head>
<body>

@foreach($bulletins as $index => $bulletin)
    @php
        $enrollment = $bulletin['enrollment'];
        $lapses = $bulletin['lapses'];
        $numericSubjects = $bulletin['numericSubjects'];
        $qualitativeSubjects = $bulletin['qualitativeSubjects'];
        $gradesMatrix = $bulletin['gradesMatrix'];
        $qualitativeGrades = $bulletin['qualitativeGrades'];
        $absencesByLapse = $bulletin['absencesByLapse'];
        $totalAbsences = $bulletin['totalAbsences'];
        $overallAverage = $bulletin['overallAverage'];
        $lapseAverages = $bulletin['lapseAverages'];
    @endphp

    <div class="bulletin-wrapper {{ $index % 2 == 0 && count($bulletins) > 1 && $index != count($bulletins) - 1 ? 'dotted-divider' : '' }}">
        
        {{-- ===== ENCABEZADO INSTITUCIONAL ===== --}}
        <table class="header-table">
            {{-- Título --}}
            <tr>
                <td colspan="4" class="title-row">BOLETÍN INFORMATIVO</td>
            </tr>
            {{-- Logo + República + Código --}}
            <tr>
                <td class="logo-cell" rowspan="3">
                    @if(!empty($settings['logo_path']) && file_exists(storage_path('app/public/' . $settings['logo_path'])))
                        <img src="{{ storage_path('app/public/' . $settings['logo_path']) }}" alt="Logo">
                    @else
                        <div class="logo-placeholder"></div>
                    @endif
                </td>
                <td colspan="2">
                    <span class="label">República Bolivariana de Venezuela</span><br>
                    <span class="value">MINISTERIO DEL PODER POPULAR PARA LA EDUCACIÓN</span>
                </td>
                <td>
                    <span class="label">Código del Plantel:</span><br>
                    <span class="value-lg">{{ $settings['school_code'] ?? '—' }}</span>
                </td>
            </tr>
            {{-- Plantel + Estudiante --}}
            <tr>
                <td colspan="2">
                    <span class="label">Plantel:</span>
                    <span class="value"> {{ strtoupper($settings['school_name'] ?? '—') }}</span>
                </td>
                <td colspan="1">
                    <span class="label">Estudiante:</span>
                    <span class="value"> {{ strtoupper($enrollment->student->last_name . ', ' . $enrollment->student->first_name) }}</span>
                </td>
            </tr>
            {{-- Municipio + Cédula + Año + Sección --}}
            <tr>
                <td>
                    <span class="label">Municipio/Estado:</span>
                    <span class="value"> {{ $settings['municipality'] ?? '—' }} — {{ $settings['state'] ?? '—' }}</span>
                </td>
                <td>
                    <span class="label">Cédula:</span>
                    <span class="value"> {{ $enrollment->student->cedula ?? '—' }}</span>
                </td>
                <td>
                    <span class="label">Año:</span>
                    <span class="value"> {{ $enrollment->section->gradeLevel->name }}</span>
                    &nbsp;&nbsp;
                    <span class="label">Sección:</span>
                    <span class="value"> {{ $enrollment->section->name }}</span>
                    &nbsp;&nbsp;
                    <span class="label">Año Escolar:</span>
                    <span class="value"> {{ $enrollment->schoolYear->name }}</span>
                </td>
            </tr>
        </table>

        {{-- ===== TABLA PRINCIPAL ===== --}}
        <table class="main-table">
            <thead>
                <tr>
                    <th class="subject-col" rowspan="2">ÁREAS DE FORMACIÓN</th>
                    @foreach($lapses as $lapse)
                        <th colspan="3" class="group-header">{{ strtoupper($lapse->name) }}</th>
                    @endforeach
                    <th colspan="{{ $lapses->count() + 1 }}" class="group-header">INASISTENCIAS</th>
                    <th class="final-col" rowspan="2">DEF.<br>DEL AÑO</th>
                    <th class="rev-col" rowspan="2">REVISIÓN</th>
                    <th class="pend-col" rowspan="2">MATERIA PENDIENTE</th>
                </tr>
                <tr>
                    @foreach($lapses as $lapse)
                        <th class="score-col">DEF. DOCENTE</th>
                        <th class="adj-col">AJUSTE</th>
                        <th class="def-col">DEF.</th>
                    @endforeach
                    @foreach($lapses as $lapse)
                        <th class="abs-col">{{ $loop->iteration }}° L</th>
                    @endforeach
                    <th class="abs-col">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                {{-- MATERIAS NUMÉRICAS --}}
                @foreach($numericSubjects as $subject)
                    @php
                        $data    = $gradesMatrix[$subject->id] ?? ['lapses' => [], 'final' => null, 'revision' => null, 'is_pending' => false];
                        $final   = $data['final'];
                        $isFailed = $final !== null && $final < 9.5;
                    @endphp
                    <tr>
                        <td class="subject-col">{{ strtoupper($subject->name) }}</td>

                        @foreach($lapses as $lapse)
                            @php $lapseData = $data['lapses'][$lapse->id] ?? null; @endphp
                            <td class="score-col {{ $lapseData && $lapseData['definitive'] < 9.5 ? 'failed' : '' }}">
                                {{ $lapseData ? number_format($lapseData['score'], 0) : '—' }}
                            </td>
                            <td class="adj-col">
                                {{ $lapseData && $lapseData['council_adjustment'] != 0 ? ($lapseData['council_adjustment'] > 0 ? '+' : '') . $lapseData['council_adjustment'] : '0' }}
                            </td>
                            <td class="def-col {{ $lapseData && $lapseData['definitive'] < 9.5 ? 'failed' : '' }}">
                                {{ $lapseData ? number_format($lapseData['definitive'], 0) : '—' }}
                            </td>
                        @endforeach

                        @foreach($lapses as $lapse)
                            <td class="abs-col">{{ $absencesByLapse[$lapse->id] ?? '—' }}</td>
                        @endforeach
                        <td class="abs-col bold">{{ $totalAbsences }}</td>

                        <td class="final-col {{ $isFailed ? 'failed' : 'approved' }}">
                            {{ $final !== null ? number_format($final, 0) : '—' }}
                        </td>

                        <td class="rev-col">
                            @if($data['revision'] !== null)
                                <span class="{{ $data['revision'] >= 9.5 ? 'approved' : 'failed' }}">
                                    {{ number_format($data['revision'], 0) }}
                                </span>
                            @else
                                <span style="color: #999;">*</span>
                            @endif
                        </td>

                        <td class="pend-col">
                            @if($data['is_pending'])
                                <span class="pending-mark">{{ strtoupper($subject->name) }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach

                {{-- PROMEDIO --}}
                <tr class="row-average">
                    <td class="subject-col">PROMEDIO</td>
                    @foreach($lapses as $lapse)
                        <td class="score-col"></td>
                        <td class="adj-col"></td>
                        <td class="def-col">
                            {{ $lapseAverages[$lapse->id] !== null ? number_format($lapseAverages[$lapse->id], 2) : '—' }}
                        </td>
                    @endforeach
                    @foreach($lapses as $lapse)
                        <td class="abs-col"></td>
                    @endforeach
                    <td class="abs-col bold">{{ $totalAbsences }}</td>
                    <td class="final-col bold" colspan="3">
                        PROMEDIO: <strong>{{ $overallAverage !== null ? number_format($overallAverage, 1) : '—' }}</strong>
                    </td>
                </tr>

                {{-- MATERIAS CUALITATIVAS --}}
                @foreach($qualitativeSubjects as $subject)
                    <tr class="row-qualitative">
                        <td class="subject-col">{{ strtoupper($subject->name) }}</td>
                        @foreach($lapses as $lapse)
                            <td class="score-col"></td>
                            <td class="adj-col"></td>
                            <td class="def-col"></td>
                        @endforeach
                        @foreach($lapses as $lapse)
                            <td class="abs-col"></td>
                        @endforeach
                        <td class="abs-col"></td>
                        <td class="final-col bold">{{ $qualitativeGrades[$subject->id] ?? '—' }}</td>
                        <td class="rev-col"></td>
                        <td class="pend-col"></td>
                    </tr>
                @endforeach
        </table>

        {{-- ===== MATERIAS PENDIENTES / ARRASTRE DE AÑOS ANTERIORES ===== --}}
        @php $subjectDebts = $bulletin['subjectDebts'] ?? collect(); @endphp
        @if($subjectDebts->isNotEmpty())
            <table class="main-table" style="margin-top: 4px; margin-bottom: 4px;">
                <thead>
                    <tr style="background: #f1f5f9;">
                        <th colspan="4" style="text-align: left; padding: 3px 6px; font-size: 8px; font-weight: bold; color: #334155; border: 1px solid #94a3b8;">
                            REGISTRO DE MATERIA PENDIENTE (ARRASTRE DE AÑOS ANTERIORES)
                        </th>
                    </tr>
                    <tr style="background: #f8fafc; font-size: 7.5px;">
                        <th style="border: 1px solid #cbd5e1; text-align: left; padding: 2px 5px; width: 40%;">ASIGNATURA ADEUDADA</th>
                        <th style="border: 1px solid #cbd5e1; text-align: center; padding: 2px 5px; width: 20%;">AÑO DE ORIGEN</th>
                        <th style="border: 1px solid #cbd5e1; text-align: center; padding: 2px 5px; width: 20%;">ESTADO / MOMENTO</th>
                        <th style="border: 1px solid #cbd5e1; text-align: center; padding: 2px 5px; width: 20%;">CALIFICACIÓN FINAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subjectDebts as $debt)
                        <tr style="font-size: 7.5px;">
                            <td style="border: 1px solid #cbd5e1; padding: 2px 5px; text-align: left; font-weight: bold;">
                                {{ strtoupper($debt->subject->name ?? 'MATERIA') }} ({{ $debt->subject->gradeLevel->name ?? '' }})
                            </td>
                            <td style="border: 1px solid #cbd5e1; text-align: center; padding: 2px 5px;">
                                {{ $debt->originSchoolYear->name ?? 'Años Anteriores' }}
                            </td>
                            <td style="border: 1px solid #cbd5e1; text-align: center; padding: 2px 5px; font-weight: bold; color: {{ $debt->status === 'resolved' ? '#15803d' : '#b45309' }};">
                                {{ $debt->status === 'resolved' ? 'SOLVENTE' : 'PENDIENTE' }} {{ $debt->moment ? '— ' . $debt->moment : '' }}
                            </td>
                            <td style="border: 1px solid #cbd5e1; text-align: center; padding: 2px 5px; font-weight: bold; font-size: 8.5px; color: {{ ($debt->score ?? 0) >= 10 ? '#15803d' : '#b45309' }};">
                                @if($debt->score !== null)
                                    {{ number_format($debt->score, 0) }} pts
                                @else
                                    {{ $debt->status === 'resolved' ? 'APROBADA' : 'POR EVALUAR' }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- ===== PIE / FIRMAS ===== --}}
        <table class="signatures-row">
            <tr>
                <td>
                    <div class="sig-line">
                        Prof. {{ $settings['control_study_name'] ?? '___________________' }}<br>
                        Control de Estudio
                    </div>
                </td>
                <td>
                    <div class="sig-line">
                        Prof. {{ $settings['director_name'] ?? '___________________' }}<br>
                        Director(a)
                    </div>
                </td>
            </tr>
        </table>
        
        <div class="footer-text">
            Documento generado por el Sistema de Gestión Escolar — {{ now()->format('d/m/Y H:i') }}
        </div>

    </div>

    {{-- Salto de página cada 2 boletines --}}
    @if($index % 2 != 0 && $index != count($bulletins) - 1)
        <div style="page-break-after: always;"></div>
    @endif
@endforeach

</body>
</html>
