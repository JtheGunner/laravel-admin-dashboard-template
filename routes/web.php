<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Debug\QueueController;
use App\Http\Controllers\Debug\ScheduleController;
use App\Http\Controllers\IconsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Support\ResourceRoutes;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Example sections built on the generic ResourceController / ResourceIndex engine.
    ResourceRoutes::register('customers', CustomerController::class, softDeletes: true);
    ResourceRoutes::register('products', ProductController::class);
    ResourceRoutes::register('categories', CategoryController::class, sortable: true);
    ResourceRoutes::register('orders', OrderController::class, actions: ['index', 'store', 'update', 'destroy', 'show']);

    // Read-only log view (append-only history).
    Route::get('logs/activity', [ActivityLogController::class, 'index'])->name('logs.activity');

    // Static showcase pages.
    Route::inertia('examples/form-fields', 'examples/FormFields')->name('examples.form-fields');
    Route::inertia('examples/wizard', 'examples/Wizard')->name('examples.wizard');
    Route::inertia('examples/components', 'examples/Components')->name('examples.components');
    Route::get('icons', IconsController::class)->name('icons');

    // Administration.
    Route::middleware('can:manage-users')->group(function () {
        ResourceRoutes::register('users', UserController::class, softDeletes: true);
    });

    // Debug: scheduler and queue inspector.
    Route::get('debug/schedule', [ScheduleController::class, 'index'])->name('debug.schedule.index');
    Route::post('debug/schedule/run', [ScheduleController::class, 'run'])
        ->name('debug.schedule.run')
        ->middleware('throttle:10,1');
    Route::get('debug/queue', [QueueController::class, 'index'])->name('debug.queue.index');
});

require __DIR__ . '/settings.php';
