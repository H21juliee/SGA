<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Imports\StudentsImport;
use App\Imports\SubjectsImport;
use App\Imports\TeachersImport;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

/**
 * ImportController — Controlador del módulo de importación masiva.
 *
 * Diseñado para escalar: cuando se agreguen importaciones adicionales,
 * solo se añaden nuevos métodos aquí y rutas en web.php.
 */
class ImportController extends Controller implements HasMiddleware
{
    use LogsActivity;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:students.import', only: ['students', 'importStudents']),
            new Middleware('permission:subjects.import', only: ['subjects', 'importSubjects']),
            new Middleware('permission:teachers.import', only: ['teachers', 'importTeachers']),
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
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ], [
            'file.required' => 'Debes seleccionar un archivo para importar.',
            'file.mimes'    => 'El archivo debe ser de tipo Excel (.xlsx, .xls) o CSV (.csv).',
            'file.max'      => 'El archivo no debe superar los 10 MB.',
        ]);

        $import = new StudentsImport();
        Excel::import($import, $request->file('file'));

        $created = $import->created;
        $skipped = $import->skipped;

        $this->auditLog('importacion', 'imported',
            "Importó estudiantes desde archivo: {$created} creado(s), {$skipped} omitido(s)"
        );

        return back()
            ->with('import_result', [
                'type'        => 'students',
                'created'     => $created,
                'skipped'     => $skipped,
                'skippedRows' => $import->skippedRows,
            ]);
    }

    // -----------------------------------------------------------------------
    // Materias
    // -----------------------------------------------------------------------

    /**
     * Muestra la página de importación de materias.
     */
    public function subjects()
    {
        return Inertia::render('Import/Subjects');
    }

    /**
     * Recibe el archivo, ejecuta la importación de materias.
     */
    public function importSubjects(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ], [
            'file.required' => 'Debes seleccionar un archivo para importar.',
            'file.mimes'    => 'El archivo debe ser de tipo Excel (.xlsx, .xls) o CSV (.csv).',
            'file.max'      => 'El archivo no debe superar los 10 MB.',
        ]);

        $import = new SubjectsImport();
        Excel::import($import, $request->file('file'));

        $created = $import->created;
        $skipped = $import->skipped;

        $this->auditLog('importacion', 'imported',
            "Importó materias desde archivo: {$created} creada(s), {$skipped} omitida(s)"
        );

        return back()
            ->with('import_result', [
                'type'        => 'subjects',
                'created'     => $created,
                'skipped'     => $skipped,
                'skippedRows' => $import->skippedRows,
            ]);
    }

    // -----------------------------------------------------------------------
    // Docentes
    // -----------------------------------------------------------------------

    /**
     * Muestra la página de importación de docentes.
     */
    public function teachers()
    {
        return Inertia::render('Import/Teachers');
    }

    /**
     * Recibe el archivo, ejecuta la importación de docentes.
     */
    public function importTeachers(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ], [
            'file.required' => 'Debes seleccionar un archivo para importar.',
            'file.mimes'    => 'El archivo debe ser de tipo Excel (.xlsx, .xls) o CSV (.csv).',
            'file.max'      => 'El archivo no debe superar los 10 MB.',
        ]);

        $import = new TeachersImport();
        Excel::import($import, $request->file('file'));

        $created = $import->created;
        $skipped = $import->skipped;

        $this->auditLog('importacion', 'imported',
            "Importó docentes desde archivo: {$created} creado(s), {$skipped} omitido(s)"
        );

        return back()
            ->with('import_result', [
                'type'        => 'teachers',
                'created'     => $created,
                'skipped'     => $skipped,
                'skippedRows' => $import->skippedRows,
            ]);
    }
}
