<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CategoryApiController;
use App\Http\Controllers\Api\V1\CustomerApiController;
use App\Http\Controllers\Api\V1\ProductApiController;
use App\Http\Controllers\Api\V1\UserApiController;
use Illuminate\Support\Facades\Route;

/*
| REST API, authenticated with a static bearer token (api.token middleware).
| Versioned via prefix('v1'); a future v2 becomes a sibling group.
| Read-only for now: remove ->only([...]) per resource to add writes.
*/
Route::prefix('v1')
    ->name('api.v1.')
    ->middleware('api.token')
    ->group(function (): void {
        $readOnly = ['index', 'show'];

        Route::apiResource('users', UserApiController::class)->only($readOnly);
        Route::apiResource('customers', CustomerApiController::class)->only($readOnly);
        Route::apiResource('products', ProductApiController::class)->only($readOnly);
        Route::apiResource('categories', CategoryApiController::class)->only($readOnly);
    });
