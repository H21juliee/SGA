<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Imports\StudentsImport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

/**
 * ImportController — Controlador del módulo de importación masiva.
 *
 * Diseñado para escalar: cuando se agreguen importaciones de representantes,
 * materias o docentes, solo se añaden nuevos métodos aquí y rutas en web.php.
 */
class ImportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:students.import', only: ['students', 'importStudents']),
            // Futuras importaciones:
            // new Middleware('permission:guardians.import', only: ['guardians', 'importGuardians']),
            // new Middleware('permission:subjects.import',  only: ['subjects',  'importSubjects']),
            // new Middleware('permission:teachers.import',  only: ['teachers',  'importTeachers']),
        ];
    }

    // -----------------------------------------------------------------------
    // Estudiantes
    // -----------------------------------------------------------------------

    /**
     * Muestra la página de importación de estudiantes.
     */
    public function students()
    {
        return Inertia::render('Import/Students');
    }

    /**
     * Recibe el archivo, ejecuta la importación y devuelve un resumen real al usuario.
     */
    public function importStudents(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240', // 10 MB máximo
            ],
        ], [
            'file.required' => 'Debes seleccionar un archivo para importar.',
            'file.mimes'    => 'El archivo debe ser de tipo Excel (.xlsx, .xls) o CSV (.csv).',
            'file.max'      => 'El archivo no debe superar los 10 MB.',
        ]);

        $import = new StudentsImport();

        // Importación síncrona directamente desde el archivo subido
        Excel::import($import, $request->file('file'));

        $created = $import->created;
        $skipped = $import->skipped;

        $message = "✅ Importación completada: {$created} estudiante(s) registrado(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} fila(s) omitida(s) (cédulas duplicadas o datos inválidos).";
        }

        return back()->with('success', $message);
    }

    // -----------------------------------------------------------------------
    // Futuras importaciones (descomentar cuando se implementen)
    // -----------------------------------------------------------------------

    // public function guardians() { ... }
    // public function importGuardians(Request $request) { ... }

    // public function subjects() { ... }
    // public function importSubjects(Request $request) { ... }

    // public function teachers() { ... }
    // public function importTeachers(Request $request) { ... }
}
