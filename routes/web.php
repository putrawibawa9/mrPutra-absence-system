<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JokiDashboardController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherAvailabilityController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherScheduleController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', JokiDashboardController::class)->name('joki.dashboard');
Route::resource('projects', ProjectController::class);
Route::post('/projects/{project}/progress', [ProgressController::class, 'store'])->name('projects.progress.store');

Route::get('/receipts/{payment}/public', [PaymentController::class, 'publicReceipt'])
    ->middleware('signed')
    ->name('payments.public-receipt');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/students', [StudentController::class, 'index'])->name('students.index');

    Route::middleware('role:'.User::ROLE_ADMIN)->group(function () {
        Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
        Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::patch('/students/{student}/toggle-status', [StudentController::class, 'toggleStatus'])->name('students.toggle-status');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

        Route::resource('packages', PackageController::class)->except(['show']);
        Route::resource('teachers', TeacherController::class)->except(['show']);
        Route::resource('teacher-schedules', TeacherScheduleController::class)->except(['show']);
        Route::resource('teacher-availabilities', TeacherAvailabilityController::class)->except(['show']);
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
        Route::post('/payments/{payment}/installments', [PaymentController::class, 'storeInstallment'])->name('payments.installments.store');
        Route::post('/payments/{payment}/reconcile-debt', [PaymentController::class, 'reconcileDebt'])->name('payments.reconcile-debt');
        Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
    });

    Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');

    Route::middleware('role:'.User::ROLE_ADMIN.','.User::ROLE_TEACHER)->group(function () {
        Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');
    });

    Route::middleware('role:'.User::ROLE_TEACHER)->group(function () {
        Route::get('/attendances/create', [AttendanceController::class, 'create'])->name('attendances.create');
        Route::post('/attendances', [AttendanceController::class, 'store'])->name('attendances.store');
        Route::get('/attendances/{attendance}/edit', [AttendanceController::class, 'edit'])->name('attendances.edit');
        Route::put('/attendances/{attendance}', [AttendanceController::class, 'update'])->name('attendances.update');
        Route::get('/my-schedule', [TeacherScheduleController::class, 'mySchedule'])->name('my-schedule.index');
        Route::get('/my-availability', [TeacherAvailabilityController::class, 'myIndex'])->name('my-availability.index');
        Route::get('/my-availability/create', [TeacherAvailabilityController::class, 'myCreate'])->name('my-availability.create');
        Route::post('/my-availability', [TeacherAvailabilityController::class, 'myStore'])->name('my-availability.store');
        Route::get('/my-availability/{teacher_availability}/edit', [TeacherAvailabilityController::class, 'myEdit'])->name('my-availability.edit');
        Route::put('/my-availability/{teacher_availability}', [TeacherAvailabilityController::class, 'myUpdate'])->name('my-availability.update');
        Route::delete('/my-availability/{teacher_availability}', [TeacherAvailabilityController::class, 'myDestroy'])->name('my-availability.destroy');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
