<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\LearningModuleController;
use App\Http\Controllers\MaterialLinkController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherAvailabilityController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherScheduleController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

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

        Route::get('/classrooms/create', [ClassroomController::class, 'create'])->name('classrooms.create');
        Route::post('/classrooms', [ClassroomController::class, 'store'])->name('classrooms.store');
        Route::get('/classrooms/{classroom}/edit', [ClassroomController::class, 'edit'])->name('classrooms.edit');
        Route::put('/classrooms/{classroom}', [ClassroomController::class, 'update'])->name('classrooms.update');
        Route::patch('/classrooms/{classroom}/toggle-status', [ClassroomController::class, 'toggleStatus'])->name('classrooms.toggle-status');
        Route::delete('/classrooms/{classroom}', [ClassroomController::class, 'destroy'])->name('classrooms.destroy');

        Route::resource('learning-modules', LearningModuleController::class)->except(['show']);
        Route::resource('material-links', MaterialLinkController::class)->except(['show']);
        Route::resource('teachers', TeacherController::class)->except(['show']);
        Route::resource('teacher-schedules', TeacherScheduleController::class)->except(['show']);
        Route::resource('teacher-availabilities', TeacherAvailabilityController::class)->except(['show']);
        Route::resource('expense-categories', ExpenseCategoryController::class)->except(['show']);
        Route::resource('expenses', ExpenseController::class)->except(['show']);
        Route::get('/cash-flow', CashFlowController::class)->name('cash-flow.index');
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
        Route::get('/classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');
        Route::get('/classrooms/{classroom}/attendances/create', [ClassroomController::class, 'createAttendance'])->name('classrooms.attendances.create');
        Route::post('/classrooms/{classroom}/attendances', [ClassroomController::class, 'storeAttendance'])->name('classrooms.attendances.store');

        Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');
        Route::get('/attendances/create', [AttendanceController::class, 'create'])->name('attendances.create');
        Route::post('/attendances', [AttendanceController::class, 'store'])->name('attendances.store');
        Route::get('/attendance-batches/{attendanceBatch}/edit', [AttendanceController::class, 'editBatch'])->name('attendances.batches.edit');
        Route::put('/attendance-batches/{attendanceBatch}', [AttendanceController::class, 'updateBatch'])->name('attendances.batches.update');
        Route::get('/attendances/{attendance}/edit', [AttendanceController::class, 'edit'])->name('attendances.edit');
        Route::put('/attendances/{attendance}', [AttendanceController::class, 'update'])->name('attendances.update');
    });

    Route::middleware('role:'.User::ROLE_TEACHER)->group(function () {
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
