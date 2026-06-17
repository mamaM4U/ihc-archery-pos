<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Coach\TemplateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
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

Route::get('/dashboard/access', function () {
    return Inertia::render('Dashboard/Access');
})->middleware(['auth'])->name('dashboard.access');

Route::group(['prefix' => 'dashboard', 'middleware' => ['auth']], function () {
    Route::get('/', [DashboardController::class, 'index'])->middleware(['auth', 'verified', 'permission:dashboard-access'])->name('dashboard');

    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:permissions-access')->name('permissions.index');

    Route::resource('/roles', RoleController::class)
        ->except(['create', 'edit', 'show'])
        ->middlewareFor('index', 'permission:roles-access')
        ->middlewareFor('store', 'permission:roles-create')
        ->middlewareFor('update', 'permission:roles-update')
        ->middlewareFor('destroy', 'permission:roles-delete');

    // User Assignment Routes
    Route::get('/users/assignments', [UserController::class, 'assignments'])
        ->middleware('permission:users-create')
        ->name('users.assignments');
    Route::post('/users/assignments/coach', [UserController::class, 'assignCoach'])
        ->middleware('permission:users-create')
        ->name('users.assign-coach');
    Route::post('/users/assignments/guardian', [UserController::class, 'assignGuardian'])
        ->middleware('permission:users-create')
        ->name('users.assign-guardian');
    Route::delete('/users/assignments/coach', [UserController::class, 'removeCoachAssignment'])
        ->middleware('permission:users-delete')
        ->name('users.remove-coach');
    Route::delete('/users/assignments/guardian', [UserController::class, 'removeGuardianAssignment'])
        ->middleware('permission:users-delete')
        ->name('users.remove-guardian');

    Route::resource('/users', UserController::class)
        ->except('show')
        ->middlewareFor('index', 'permission:users-access')
        ->middlewareFor(['create', 'store'], 'permission:users-create')
        ->middlewareFor(['edit', 'update'], 'permission:users-update')
        ->middlewareFor('destroy', 'permission:users-delete');

    Route::resource('/templates', TemplateController::class)
        ->middlewareFor('index', 'permission:templates-access')
        ->middlewareFor('create', 'permission:templates-create')
        ->middlewareFor('store', 'permission:templates-create')
        ->middlewareFor('edit', 'permission:templates-update')
        ->middlewareFor('update', 'permission:templates-update')
        ->middlewareFor('destroy', 'permission:templates-delete');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/logout', function () {
    Auth::guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout.get');

require __DIR__.'/auth.php';
