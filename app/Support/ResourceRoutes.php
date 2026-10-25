<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\Controllers\ResourceController;
use Illuminate\Support\Facades\Route;

/**
 * Registers the full route set that the generic `ResourceIndex.vue` table expects
 * from a {@see ResourceController}: CRUD, bulk actions and (optionally) the
 * soft-delete and drag-and-drop reorder endpoints.
 */
final class ResourceRoutes {
    /**
     * @param  class-string<ResourceController<*>>  $controller
     * @param  list<string>  $actions  Resource actions to register (e.g. adds `show`).
     */
    public static function register(
        string $uri,
        string $controller,
        bool $softDeletes = false,
        bool $sortable = false,
        array $actions = ['index', 'store', 'update', 'destroy'],
    ): void {
        Route::post("$uri/bulk-destroy", [$controller, 'bulkDestroy'])->name("$uri.bulk-destroy");

        if ($softDeletes) {
            Route::post("$uri/bulk-restore", [$controller, 'bulkRestore'])->name("$uri.bulk-restore");
            Route::post("$uri/bulk-force-destroy", [$controller, 'bulkForceDestroy'])->name("$uri.bulk-force-destroy");
            Route::post("$uri/{id}/restore", [$controller, 'restore'])->name("$uri.restore");
            Route::delete("$uri/{id}/force", [$controller, 'forceDestroy'])->name("$uri.force-destroy");
        }

        if ($sortable) {
            Route::post("$uri/reorder", [$controller, 'reorder'])->name("$uri.reorder");
        }

        Route::resource($uri, $controller)->only($actions);
    }
}
