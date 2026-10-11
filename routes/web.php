<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\PreTestController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivitySchemeController;
use App\Http\Controllers\MembershipController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::post('/language', function (Request $request) {
    $data = $request->validate(['locale' => ['required', \Illuminate\Validation\Rule::in(['id', 'en'])]]);
    $request->session()->put('locale', $data['locale']);

    return back(303)->withCookie(cookie('ec_locale', $data['locale'], 60 * 24 * 365, '/', null, $request->isSecure(), true));
})->name('locale.update');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/notifikasi', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifikasi/ringkasan', [\App\Http\Controllers\NotificationController::class, 'summary'])->name('notifications.summary');
    Route::post('/notifikasi/baca-semua', [\App\Http\Controllers\NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifikasi/{notification}/baca', [\App\Http\Controllers\NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifikasi/{notification}/buka', [\App\Http\Controllers\NotificationController::class, 'open'])->name('notifications.open');
    Route::get('/kegiatan/buat', [ActivityController::class, 'create'])->name('activities.create');
    Route::get('/kegiatan/histori', [\App\Http\Controllers\ActivityHistoryController::class, 'index'])->name('activities.history');
    Route::post('/kegiatan', [ActivityController::class, 'store'])->name('activities.store');
    Route::get('/kegiatan', [ActivityController::class, 'index'])->name('activities.index');
    Route::post('/kegiatan/{activity}/presensi', [ActivityController::class, 'recordAttendance'])->name('activities.attendance');
    Route::post('/kegiatan/{activity}/berkas', [\App\Http\Controllers\ActivityAttachmentController::class, 'store'])->name('activities.attachments.store');
    Route::get('/kegiatan/{activity}/berkas/{attachment}/unduh', [\App\Http\Controllers\ActivityAttachmentController::class, 'download'])->name('activities.attachments.download');
    Route::get('/kegiatan/{activity}/berkas/{attachment}/pratinjau', [\App\Http\Controllers\ActivityAttachmentController::class, 'preview'])->name('activities.attachments.preview');
    Route::delete('/kegiatan/{activity}/berkas/{attachment}', [\App\Http\Controllers\ActivityAttachmentController::class, 'destroy'])->name('activities.attachments.destroy');
    Route::get('/pre-test', [PreTestController::class, 'index'])->name('pretests.index');
    Route::post('/pre-test', [PreTestController::class, 'store'])->name('pretests.store');
    Route::get('/pre-test/siswa/{student}', [PreTestController::class, 'show'])->name('pretests.show');
    Route::middleware(\App\Http\Middleware\EnsureActiveCommittee::class)->group(function () {
        Route::get('/panitia/dashboard', [AssessmentController::class, 'committeeDashboard'])->name('panitia.dashboard');
        Route::get('/panitia/penugasan', [AssessmentController::class, 'committeeIndex'])->name('panitia.assignments');
        Route::post('/penilaian/{assignment}/rekomendasi', [AssessmentController::class, 'recommend'])->name('assessments.recommend');
    });
    Route::get('/penilaian/{assignment}', [AssessmentController::class, 'show'])->name('assessments.show');
    Route::get('/perkembangan-saya', [AssessmentController::class, 'progress'])->name('progress.index');
    Route::get('/laporan/siswa/{student}', [\App\Http\Controllers\ReportController::class, 'student'])->name('reports.student');

    Route::get('/students/{student}/photo', [\App\Http\Controllers\StudentController::class, 'photo'])->name('students.photo');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware([\App\Http\Middleware\EnsurePembina::class])->group(function () {
        Route::get('/laporan/arsip', [\App\Http\Controllers\DataTransferController::class, 'index'])->name('data-transfer.index');
        Route::get('/laporan/arsip/ekspor', [\App\Http\Controllers\DataTransferController::class, 'export'])->name('data-transfer.export');
        Route::get('/laporan/arsip/template', [\App\Http\Controllers\DataTransferController::class, 'template'])->name('data-transfer.template');
        Route::get('/laporan/arsip/cetak', [\App\Http\Controllers\DataTransferController::class, 'print'])->name('data-transfer.print');
        Route::post('/laporan/arsip/pratinjau', [\App\Http\Controllers\DataTransferController::class, 'preview'])->name('data-transfer.preview');
        Route::post('/laporan/arsip/impor', [\App\Http\Controllers\DataTransferController::class, 'import'])->name('data-transfer.import');
        Route::delete('/laporan/arsip/pratinjau', [\App\Http\Controllers\DataTransferController::class, 'cancel'])->name('data-transfer.cancel');
        Route::resource('panitia-ec', \App\Http\Controllers\CommitteeRoleController::class)
            ->only(['index', 'store', 'update', 'destroy'])->names('committee-roles')->parameters(['panitia-ec' => 'committeeRole']);
        Route::get('/tahun-ajaran', [AcademicYearController::class, 'index'])->name('academic-years.index');
        Route::post('/tahun-ajaran', [AcademicYearController::class, 'store'])->name('academic-years.store');
        Route::post('/tahun-ajaran/{academicYear}/aktifkan', [AcademicYearController::class, 'activate'])->name('academic-years.activate');
        Route::get('/keanggotaan', [MembershipController::class, 'index'])->name('memberships.index');
        Route::post('/keanggotaan', [MembershipController::class, 'store'])->name('memberships.store');
        Route::put('/keanggotaan/{membership}', [MembershipController::class, 'update'])->name('memberships.update');
        Route::delete('/keanggotaan/{membership}', [MembershipController::class, 'destroy'])->name('memberships.destroy');
        Route::get('/skema-kegiatan', [ActivitySchemeController::class, 'index'])->name('activity-schemes.index');
        Route::post('/skema-kegiatan', [ActivitySchemeController::class, 'store'])->name('activity-schemes.store');
        Route::put('/skema-kegiatan/{activityScheme}', [ActivitySchemeController::class, 'update'])->name('activity-schemes.update');
        Route::delete('/skema-kegiatan/{activityScheme}', [ActivitySchemeController::class, 'destroy'])->name('activity-schemes.destroy');
        Route::get('/kegiatan/{activity}/edit', [ActivityController::class, 'edit'])->name('activities.edit');
        Route::put('/kegiatan/{activity}', [ActivityController::class, 'update'])->name('activities.update');
        Route::delete('/kegiatan/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');
        Route::post('/students/status-massal', [\App\Http\Controllers\StudentController::class, 'bulkStatus'])->name('students.bulk-status');
        Route::patch('/students/{student}/status', [\App\Http\Controllers\StudentController::class, 'updateStatus'])->name('students.status');
        Route::get('/pre-test/hasil', [PreTestController::class, 'reports'])->name('pretests.reports');
        Route::get('/laporan/minat', [\App\Http\Controllers\ReportController::class, 'interests'])->name('reports.interests');
        Route::get('/penilaian', [AssessmentController::class, 'index'])->name('assessments.index');
        Route::get('/pemeriksaan-rekomendasi', [AssessmentController::class, 'reviewQueue'])->name('assessments.queue');
        Route::post('/penilaian', [AssessmentController::class, 'store'])->name('assessments.store');
        Route::post('/penilaian/{assignment}/batas-waktu', [AssessmentController::class, 'extendDeadline'])->name('assessments.deadline');
        Route::post('/penilaian/{assignment}/keputusan', [AssessmentController::class, 'review'])->name('assessments.review');

        Route::resource('students', \App\Http\Controllers\StudentController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    });
    Route::get('/kegiatan/{activity}', [ActivityController::class, 'show'])->name('activities.show');
});

require __DIR__.'/auth.php';
