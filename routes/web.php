<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AssessmentController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function (Request $request) {
    if ($request->user()->isPanitia()) { return redirect()->route('panitia.dashboard'); }
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/panitia/dashboard', [AssessmentController::class, 'committeeDashboard'])->name('panitia.dashboard');
    Route::get('/panitia/penugasan', [AssessmentController::class, 'committeeIndex'])->name('panitia.assignments');
    Route::get('/penilaian/{assignment}', [AssessmentController::class, 'show'])->name('assessments.show');
    Route::post('/penilaian/{assignment}/rekomendasi', [AssessmentController::class, 'recommend'])->name('assessments.recommend');
    Route::get('/perkembangan-saya', [AssessmentController::class, 'progress'])->name('progress.index');

    Route::get('/students/{student}/photo', [\App\Http\Controllers\StudentController::class, 'photo'])->name('students.photo');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware([\App\Http\Middleware\EnsurePembina::class])->group(function () {
        Route::get('/penilaian', [AssessmentController::class, 'index'])->name('assessments.index');
        Route::post('/penilaian', [AssessmentController::class, 'store'])->name('assessments.store');
        Route::post('/penilaian/{assignment}/batas-waktu', [AssessmentController::class, 'extendDeadline'])->name('assessments.deadline');
        Route::post('/penilaian/{assignment}/keputusan', [AssessmentController::class, 'review'])->name('assessments.review');

        Route::resource('students', \App\Http\Controllers\StudentController::class)
            ->only(['index', 'create', 'store', 'edit', 'update']);
    });
});

require __DIR__.'/auth.php';
