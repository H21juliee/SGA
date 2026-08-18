<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        /* Márgenes ultra reducidos */
        @page { margin: 10px 20px; }
        
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
            margin-bottom: 2px; 
            font-size: 10px;
        }
        
        /* Tabla ultra compacta */
        table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        th, td { 
            border: 1px solid #7f8c8d; 
            padding: 2px 4px; 
            text-align: left; 
            line-height: 1;
        }
        th { 
            background: #ecf0f1; 
            font-weight: bold;
            font-size: 9px;
            color: #2c3e50;
        }
        td {
            font-size: 9px; 
        }
        
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Listado de Estudiantes</h1>
        <div class="info">
            <strong>Año Escolar:</strong> {{ $year->name }} &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp; 
            <strong>Grado:</strong> {{ $section->gradeLevel->name }} &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
            <strong>Sección:</strong> {{ $section->name }}
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">N°</th>
                <th style="width: 20%;">Cédula</th>
                <th style="width: 35%;">Apellidos</th>
                <th style="width: 40%;">Nombres</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $i => $student)
                <tr>
                    <td style="text-align: center;">{{ $i + 1 }}</td>
                    <td>{{ $student->cedula }}</td>
                    <td>{{ $student->last_name }}</td>
                    <td>{{ $student->first_name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>