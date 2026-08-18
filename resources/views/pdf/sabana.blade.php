<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        /* Márgenes ultra reducidos y orientación landscape manejada por DomPDF via setPaper */
        @page { margin: 10px 15px; }
        
        body { 
            font-family: DejaVu Sans, sans-serif; 
            margin: 0;
            padding: 0;
            color: #333;
        }
        h1 { 
            text-align: center; 
            font-size: 14px; 
            margin: 2px 0;
            text-transform: uppercase;
        }
        .header { 
            text-align: center;
            margin-bottom: 5px; 
            border-bottom: 1px solid #2c3e50;
            padding-bottom: 2px;
        }
        .info { 
            display: block;
            margin-bottom: 5px; 
            font-size: 10px;
        }
        
        /* Tabla ultra compacta */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed;
        }
        th, td { 
            border: 1px solid #7f8c8d; 
            padding: 2px 3px; 
            text-align: center; 
            line-height: 1;
            overflow: hidden;
            word-wrap: break-word;
        }
        th { 
            background: #ecf0f1; 
            font-weight: bold;
            font-size: 8px;
            color: #2c3e50;
        }
        td {
            font-size: 9px; 
        }
        td.left-align {
            text-align: left;
        }
        
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sábana de Notas - Lapso {{ $lapse->order_num }}</h1>
        <div class="info">
            <strong>Año Escolar:</strong> {{ $section->schoolYear->name }} &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp; 
            <strong>Grado:</strong> {{ $section->gradeLevel->name }} &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
            <strong>Sección:</strong> {{ $section->name }}
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 3%;">N°</th>
                <th style="width: 10%;">Cédula</th>
                <th style="width: 25%; text-align: left;">Apellidos y Nombres</th>
                @foreach($subjects as $subject)
                    <th>{{ $subject->code ?? substr($subject->name, 0, 3) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($enrollments as $i => $enrollment)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $enrollment->student->cedula }}</td>
                    <td class="left-align">{{ $enrollment->student->last_name }}, {{ $enrollment->student->first_name }}</td>
                    @foreach($subjects as $subject)
                        @php
                            $grade = $enrollment->grades->where('subject_id', $subject->id)->first();
                            $score = '—';
                            if ($grade) {
                                if ($subject->grading_type === 'qualitative') {
                                    $score = $grade->score;
                                } else {
                                    // Si el score decimal tiene .00, mostrar solo el entero para ahorrar espacio visual
                                    $val = $grade->definitive;
                                    $score = fmod($val, 1) == 0 ? (int)$val : number_format($val, 1);
                                }
                            }
                        @endphp
                        <td>{{ $score }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>