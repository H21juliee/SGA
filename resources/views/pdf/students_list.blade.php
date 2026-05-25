<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 20px; }
        h1 { text-align: center; }
        .info { margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 5px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Listado de Estudiantes</h1>
    <div class="info">
        <strong>Año escolar:</strong> {{ $year->name }}<br>
        <strong>Sección:</strong> {{ $section->name }} ({{ $section->gradeLevel->name }})
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Cédula</th>
                <th>Apellidos</th>
                <th>Nombres</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $i => $student)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $student->cedula }}</td>
                    <td>{{ $student->last_name }}</td>
                    <td>{{ $student->first_name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
