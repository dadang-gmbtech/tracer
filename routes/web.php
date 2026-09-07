<?php

use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\ProgramStudyController;
use App\Http\Controllers\Admin\ProvinceController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\UmpController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AlumniController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployerDashboardController;
use App\Http\Controllers\EmployerImportController;
use App\Http\Controllers\EmployerResponseController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TracerResponseController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Custom auth (SSO stub + NIM/tanggal lahir) — routes/auth.php keeps email+password for staff.
Route::middleware('guest')->group(function () {
    Route::get('/login/sso', [LoginController::class, 'loginSso'])->name('login.sso');
    Route::post('/login/nim', [LoginController::class, 'loginNim'])->name('login.nim');
});

// Registered before the public signed-URL group below so these static paths
// (auth-required) never fall through to the /pengguna-alumni/{alumni} wildcard.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard/pengguna-alumni', [EmployerDashboardController::class, 'index'])->name('employer.dashboard');
    Route::get('/pengguna-alumni/import', [EmployerImportController::class, 'importForm'])->name('employer.import.form');
    Route::get('/pengguna-alumni/import/template', [EmployerImportController::class, 'template'])->name('employer.import.template');
    Route::post('/pengguna-alumni/import', [EmployerImportController::class, 'import'])->name('employer.import');
});

// Public: Form Pengguna Alumni, no login — reached via a temporary signed link.
// The static "terima-kasih" route must be registered before the {alumni} wildcard.
Route::get('/pengguna-alumni/terima-kasih', [EmployerResponseController::class, 'thanks'])->name('employer.thanks');
Route::middleware('signed')->group(function () {
    Route::get('/pengguna-alumni/{alumni}', [EmployerResponseController::class, 'show'])->name('employer.show');
    Route::post('/pengguna-alumni/{alumni}', [EmployerResponseController::class, 'store'])->name('employer.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/tracer/import', [TracerResponseController::class, 'importForm'])->name('tracer.import.form');
    Route::get('/tracer/import/template', [TracerResponseController::class, 'template'])->name('tracer.import.template');
    Route::post('/tracer/import', [TracerResponseController::class, 'import'])->name('tracer.import');

    Route::get('/alumni/import', [AlumniController::class, 'importForm'])->name('alumni.import.form');
    Route::get('/alumni/import/template', [AlumniController::class, 'template'])->name('alumni.import.template');
    Route::post('/alumni/import', [AlumniController::class, 'import'])->name('alumni.import');

    Route::get('/alumni', [AlumniController::class, 'index'])->name('alumni.index');
    Route::get('/alumni/{alumni}', [AlumniController::class, 'show'])->name('alumni.show');
    Route::get('/alumni/{alumni}/tracer', [TracerResponseController::class, 'edit'])->name('tracer.edit');
    Route::put('/alumni/{alumni}/tracer', [TracerResponseController::class, 'update'])->name('tracer.update');
    Route::post('/alumni/{alumni}/tracer/share-link', [TracerResponseController::class, 'shareEmployerLink'])->name('tracer.share-link');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');
    Route::delete('/reports/{report}', [ReportController::class, 'destroy'])->name('reports.destroy');

    Route::get('/export/tracer/form', [ExportController::class, 'tracerForm'])->name('export.tracer.form');
    Route::get('/export/tracer', [ExportController::class, 'tracer'])->name('export.tracer');
    Route::get('/export/alumni', [ExportController::class, 'alumni'])->name('export.alumni');
    Route::get('/export/dashboard', [ExportController::class, 'dashboard'])->name('export.dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('users/import', [UserController::class, 'importForm'])->name('users.import.form');
        Route::get('users/template', [UserController::class, 'template'])->name('users.template');
        Route::post('users/import', [UserController::class, 'import'])->name('users.import');
        Route::post('users/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
        Route::resource('users', UserController::class)->except(['show']);

        Route::resource('faculties', FacultyController::class)->except(['show']);
        Route::resource('program-studies', ProgramStudyController::class)
            ->except(['show'])
            ->parameters(['program-studies' => 'programStudy']);

        Route::resource('provinces', ProvinceController::class)->except(['show']);

        Route::get('cities/template', [CityController::class, 'template'])->name('cities.template');
        Route::post('cities/import', [CityController::class, 'import'])->name('cities.import');
        Route::resource('cities', CityController::class)->except(['show']);

        Route::get('ump/template', [UmpController::class, 'template'])->name('ump.template');
        Route::post('ump/import', [UmpController::class, 'import'])->name('ump.import');
        Route::resource('ump', UmpController::class)->except(['show']);

        Route::resource('questions', QuestionController::class)->except(['show']);
    });
});

require __DIR__.'/auth.php';
