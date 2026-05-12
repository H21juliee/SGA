<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boleta de Notas</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1e1b4b; padding-bottom: 10px; }
        .school-name { font-size: 20px; font-weight: bold; color: #312e81; }
        .school-year { font-size: 14px; color: #6b7280; margin-top: 5px; }
        .student-info { margin-bottom: 20px; width: 100%; border-collapse: collapse; }
        .student-info td { padding: 5px; border-bottom: 1px solid #e5e7eb; }
        .student-info strong { color: #1e1b4b; }
        .grades-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .grades-table th, .grades-table td { border: 1px solid #9ca3af; padding: 8px; text-align: center; }
        .grades-table th { background-color: #f3f4f6; color: #1f2937; font-size: 11px; text-transform: uppercase; }
        .subject-name { text-align: left; font-weight: bold; }
        .footer { margin-top: 50px; text-align: center; font-size: 10px; color: #6b7280; }
        .signatures { width: 100%; margin-top: 60px; text-align: center; }
        .signature-line { width: 200px; border-top: 1px solid #000; margin: 0 auto; padding-top: 5px; }
        .text-red { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <div class="school-name">Sistema de Gestión Escolar (SGE)</div>
        <div class="school-year">Boleta de Calificaciones — Año Escolar {{ $enrollment->schoolYear->name }}</div>
    </div>

    <table class="student-info">
        <tr>
            <td width="15%"><strong>Estudiante:</strong></td>
            <td width="45%">{{ $enrollment->student->full_name }}</td>
            <td width="15%"><strong>Cédula:</strong></td>
            <td width="25%">{{ $enrollment->student->cedula ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Nivel/Año:</strong></td>
            <td>{{ $enrollment->section->gradeLevel->name }}</td>
            <td><strong>Sección:</strong></td>
            <td>{{ $enrollment->section->name }}</td>
        </tr>
    </table>

    <table class="grades-table">
        <thead>
            <tr>
                <th width="40%" class="subject-name">Materia</th>
                @foreach($lapses as $lapse)
                    <th width="15%">{{ $lapse->name }}</th>
                @endforeach
                <th width="15%">Nota Final</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subjects as $subject)
                <tr>
                    <td class="subject-name">{{ $subject->name }}</td>
                    @foreach($lapses as $lapse)
                        @php
                            $grade = $enrollment->grades->where('subject_id', $subject->id)->where('lapse_id', $lapse->id)->first();
                            $score = $grade ? $grade->score : '—';
                        @endphp
                        <td>{{ $score }}</td>
                    @endforeach
                    @php
                        $final = $finalGrades[$subject->id];
                    @endphp
                    <td class="{{ $final !== null && $final < 10 ? 'text-red' : '' }}">
                        <strong>{{ $final ?? '—' }}</strong>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 13px;">
        <strong>Promedio General:</strong> 
        <span class="{{ $overallAverage !== null && $overallAverage < 10 ? 'text-red' : '' }}">
            {{ $overallAverage ?? '—' }} / 20
        </span>
        <br><br>
        <strong>Inasistencias Acumuladas:</strong> {{ $absences }}
    </div>

    <table class="signatures">
        <tr>
            <td width="50%">
                <div class="signature-line">Firma del Director(a)</div>
            </td>
            <td width="50%">
                <div class="signature-line">Firma del Representante</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Documento generado por el Sistema de Gestión Escolar el {{ now()->format('d/m/Y H:i') }}.
    </div>

</body>
</html>
