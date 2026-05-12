<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GradeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Rutas Protegidas
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/dashboard', DashboardController::class);

    // Notas
    Route::prefix('grades')->name('grades.')->group(function () {
        Route::get('/', [GradeController::class, 'index'])->name('index');
        Route::get('/{section}/{subject}/{lapse}', [GradeController::class, 'datagrid'])->name('datagrid');
        Route::patch('/', [GradeController::class, 'update'])->name('update');
        Route::post('/batch', [GradeController::class, 'batchUpdate'])->name('batch');
    });

    // Asistencia
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::get('/{section}', [AttendanceController::class, 'datagrid'])->name('datagrid');
        Route::patch('/', [AttendanceController::class, 'update'])->name('update');
        Route::post('/batch', [AttendanceController::class, 'batchUpdate'])->name('batch');
        Route::post('/lock', [AttendanceController::class, 'lock'])->name('lock');
        Route::get('/history/{section}/{subject}/{lapse}', [AttendanceController::class, 'history'])->name('history');
    });

    // Reportes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ReportController::class, 'index'])->name('index');
        Route::get('/download/{enrollment}', [\App\Http\Controllers\ReportController::class, 'downloadReportCard'])->name('download');
    });

    // Módulo de Administración (Solo roles autorizados)
    Route::prefix('admin')->name('admin.')->middleware(['role:SuperAdmin|Administrador|Secretaria'])->group(function () {
        Route::resource('students', \App\Http\Controllers\Admin\StudentController::class)->except(['create', 'show', 'edit']);
        Route::resource('school-years', \App\Http\Controllers\Admin\SchoolYearController::class)->except(['create', 'show', 'edit']);
        Route::post('school-years/{school_year}/toggle', [\App\Http\Controllers\Admin\SchoolYearController::class, 'toggleActive'])->name('school-years.toggle');
        Route::post('school-years/{school_year}/promote', [\App\Http\Controllers\Admin\SchoolYearController::class, 'closeAndPromote'])->name('school-years.promote');
        Route::post('lapses/{lapse}/toggle', [\App\Http\Controllers\Admin\SchoolYearController::class, 'toggleLapse'])->name('lapses.toggle');
        Route::resource('sections', \App\Http\Controllers\Admin\SectionController::class)->except(['create', 'show', 'edit']);
        Route::resource('subjects', \App\Http\Controllers\Admin\SubjectController::class)->except(['create', 'show', 'edit']);
        Route::resource('academic-loads', \App\Http\Controllers\Admin\AcademicLoadController::class)->except(['create', 'show', 'edit']);
        Route::resource('enrollments', \App\Http\Controllers\Admin\EnrollmentController::class)->except(['create', 'show', 'edit']);
    });
});
