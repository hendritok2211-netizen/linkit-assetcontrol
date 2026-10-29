<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LaptopController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegistration'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->middleware('role:admin,employee')->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->middleware('role:admin,employee')->name('profile.edit');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->middleware('role:admin,employee')->name('profile.password');

    Route::prefix('admin')->middleware('role:admin')->group(function (): void {
        Route::resource('divisions', DivisionController::class)->except('show');

        Route::get('/laptops', [LaptopController::class, 'index'])->name('laptops.index');
        Route::get('/laptops/create', [LaptopController::class, 'create'])->name('laptops.create');
        Route::post('/laptops', [LaptopController::class, 'store'])->name('laptops.store');
        Route::get('/laptops/{laptop}/edit', [LaptopController::class, 'edit'])->name('laptops.edit');
        Route::put('/laptops/{laptop}', [LaptopController::class, 'update'])->name('laptops.update');
        Route::delete('/laptops/{laptop}', [LaptopController::class, 'destroy'])->name('laptops.destroy');
        Route::get('/laptops/{laptop}/label', [LaptopController::class, 'label'])->name('laptops.label');
        Route::get('/laptops/{laptop}/qr', [LaptopController::class, 'qr'])->name('laptops.qr');
        Route::get('/scan', [LaptopController::class, 'scan'])->name('laptops.scan');
        Route::post('/scan', [LaptopController::class, 'verify'])->name('laptops.verify');
        Route::post('/laptops/{laptop}/audit', [LaptopController::class, 'audit'])->name('laptops.audit');

        Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
        Route::post('/loans', [LoanController::class, 'issue'])->name('loans.issue');
        Route::post('/loans/{loan}/approve-return', [LoanController::class, 'approveReturn'])->name('loans.approve-return');
        Route::post('/loans/{loan}/reject-return', [LoanController::class, 'rejectReturn'])->name('loans.reject-return');

        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::post('/employees/{user}/approve', [EmployeeController::class, 'approve'])->name('employees.approve');
        Route::post('/employees/{user}/toggle-active', [EmployeeController::class, 'toggleActive'])->name('employees.toggle-active');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    });

    Route::prefix('employee')->middleware('role:employee')->group(function (): void {
        Route::get('/loans', [LoanController::class, 'mine'])->name('loans.mine');
        Route::post('/loans/{loan}/request-return', [LoanController::class, 'requestReturn'])->name('loans.request-return');
    });
});
