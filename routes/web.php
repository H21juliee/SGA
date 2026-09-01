<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordSetupController;
use App\Http\Controllers\Auth\PasswordRecoveryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\RevisionController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\SchoolSettingController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\CouncilAdjustmentController;
use App\Http\Controllers\Import\ImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    // Password Recovery (no auth needed)
    Route::get('/password/recover', [PasswordRecoveryController::class, 'show'])->name('password.recover');
    Route::post('/password/recover/find', [PasswordRecoveryController::class, 'findUser'])->name('password.recover.find');
    Route::post('/password/recover/reset', [PasswordRecoveryController::class, 'verifyAndReset'])->name('password.recover.reset');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Password Setup (first login - auth required but NO password.changed middleware)
Route::middleware('auth')->group(function () {
    Route::get('/password/setup', [PasswordSetupController::class, 'show'])->name('password.setup');
    Route::post('/password/setup', [PasswordSetupController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Rutas Protegidas
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'password.changed'])->group(function () {
    // Dashboard
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/dashboard', DashboardController::class);

    // Notas
    Route::prefix('grades')->name('grades.')->middleware(['permission:grades.view'])->group(function () {
        Route::get('/', [GradeController::class, 'index'])->name('index');
        Route::get('/{section}/{subject}/{lapse}', [GradeController::class, 'datagrid'])->name('datagrid');
        Route::patch('/', [GradeController::class, 'update'])->name('update')->middleware('permission:grades.edit');
        Route::post('/batch', [GradeController::class, 'batchUpdate'])->name('batch')->middleware('permission:grades.edit');
    });

    // Revisiones
    Route::prefix('revisions')->name('revisions.')->middleware(['permission:revisions.view'])->group(function () {
        Route::get('/', [RevisionController::class, 'index'])->name('index');
        Route::get('/{section}/{subject}', [RevisionController::class, 'datagrid'])->name('datagrid');
        Route::patch('/', [RevisionController::class, 'update'])->name('update')->middleware('permission:revisions.edit');
        Route::post('/batch', [RevisionController::class, 'batchUpdate'])->name('batch')->middleware('permission:revisions.edit');
    });

    // Asistencia
    Route::prefix('attendance')->name('attendance.')->middleware(['permission:attendance.view'])->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::get('/{section}', [AttendanceController::class, 'datagrid'])->name('datagrid');
        Route::patch('/', [AttendanceController::class, 'update'])->name('update')->middleware('permission:attendance.manage');
        Route::post('/batch', [AttendanceController::class, 'batchUpdate'])->name('batch')->middleware('permission:attendance.manage');
        Route::post('/lock', [AttendanceController::class, 'lock'])->name('lock')->middleware('permission:attendance.manage');
        Route::get('/history/{section}/{subject}/{lapse}', [AttendanceController::class, 'history'])->name('history');
    });

    // Reportes
    Route::prefix('reports')->name('reports.')->middleware(['permission:reports.generate'])->group(function () {
        Route::get('/', [\App\Http\Controllers\ReportController::class, 'index'])->name('index');
        Route::get('/download/{enrollment}', [\App\Http\Controllers\ReportController::class, 'downloadReportCard'])->name('download');
        Route::get('/download-batch/{section}', [\App\Http\Controllers\ReportController::class, 'downloadBatchReportCards'])->name('download-batch');
        Route::get('/print-students/{year}/{section}', [\App\Http\Controllers\ReportController::class, 'printStudents'])->name('students.print');
        Route::get('/print-sabana/{section}/{lapse}', [\App\Http\Controllers\ReportController::class, 'printSabana'])->name('sabana.print');
    });

    // Módulo de Administración (Solo roles autorizados)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->except(['create', 'edit']);
        Route::post('users/{user}/reset-password', [\App\Http\Controllers\Admin\UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::get('guardians/search', [\App\Http\Controllers\GuardianController::class, 'search'])->name('guardians.search');
        Route::post('guardians', [\App\Http\Controllers\GuardianController::class, 'store'])->name('guardians.store');
        Route::resource('students', \App\Http\Controllers\Admin\StudentController::class)->except(['create', 'edit']);
        Route::resource('school-years', \App\Http\Controllers\Admin\SchoolYearController::class)->except(['create', 'show', 'edit']);
        Route::post('school-years/{school_year}/toggle', [\App\Http\Controllers\Admin\SchoolYearController::class, 'toggleActive'])->name('school-years.toggle');
        Route::post('school-years/{school_year}/promote', [\App\Http\Controllers\Admin\SchoolYearController::class, 'closeAndPromote'])->name('school-years.promote');
        Route::post('lapses/{lapse}/toggle', [\App\Http\Controllers\Admin\SchoolYearController::class, 'toggleLapse'])->name('lapses.toggle');
        Route::resource('sections', \App\Http\Controllers\Admin\SectionController::class)->except(['create', 'show', 'edit']);
        Route::resource('subjects', \App\Http\Controllers\Admin\SubjectController::class)->except(['create', 'show', 'edit']);
        Route::post('academic-loads/assign', [\App\Http\Controllers\Admin\AcademicLoadController::class, 'assign'])->name('academic-loads.assign');
        Route::resource('academic-loads', \App\Http\Controllers\Admin\AcademicLoadController::class)->except(['create', 'show', 'edit']);
        Route::resource('enrollments', \App\Http\Controllers\Admin\EnrollmentController::class)->except(['create', 'show', 'edit']);
        Route::patch('enrollments/{enrollment}/status', [\App\Http\Controllers\Admin\EnrollmentController::class, 'updateStatus'])->name('enrollments.status');
        Route::patch('enrollments/{enrollment}/transfer', [\App\Http\Controllers\Admin\EnrollmentController::class, 'transfer'])->name('enrollments.transfer');
        // Configuración institucional
        Route::get('settings', [SchoolSettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SchoolSettingController::class, 'update'])->name('settings.update');
        Route::post('settings/logo', [SchoolSettingController::class, 'uploadLogo'])->name('settings.logo');

        // Roles y Permisos
        Route::resource('roles', RoleController::class)->except(['create', 'edit']);

        // Ajuste de Consejo
        Route::get('council-adjustments', [CouncilAdjustmentController::class, 'index'])->name('council-adjustments.index');
        Route::patch('council-adjustments', [CouncilAdjustmentController::class, 'update'])->name('council-adjustments.update');
        Route::post('council-adjustments/batch', [CouncilAdjustmentController::class, 'batchUpdate'])->name('council-adjustments.batch');

        // Auditoría y Trazabilidad
        Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
    });

    // -----------------------------------------------------------------------
    // Importación de Datos — módulo independiente de Administración
    // -----------------------------------------------------------------------
    Route::prefix('import')->name('import.')->group(function () {
        // Estudiantes
        Route::get('students',  [ImportController::class, 'students'])->name('students');
        Route::post('students', [ImportController::class, 'importStudents'])->name('students.process');

        // Materias
        Route::get('subjects',  [ImportController::class, 'subjects'])->name('subjects');
        Route::post('subjects', [ImportController::class, 'importSubjects'])->name('subjects.process');

        // Docentes
        Route::get('teachers',  [ImportController::class, 'teachers'])->name('teachers');
        Route::post('teachers', [ImportController::class, 'importTeachers'])->name('teachers.process');
    });
});
