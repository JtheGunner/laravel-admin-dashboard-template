<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only log example: only the index route is registered and the page passes the
 * `readonly` prop to the table.
 *
 * @extends ResourceController<ActivityLog>
 */
class ActivityLogController extends ResourceController {
    protected function modelClass(): string {
        return ActivityLog::class;
    }

    protected function page(): string {
        return 'logs/activity/Index';
    }

    protected function singularLabel(): string {
        return 'Activity';
    }

    protected function searchableColumns(): array {
        return ['event', 'subject_type', 'description'];
    }

    protected function defaultOrderColumn(): string {
        return 'created_at';
    }

    protected function defaultOrderDirection(): string {
        return 'desc';
    }

    protected function eagerLoad(): array {
        return ['user'];
    }

    protected function transformRow(Model $model): array {
        /** @var ActivityLog $model */
        $data = $model->toArray();
        $data['user_name'] = $model->user?->name;

        return $data;
    }

    protected function rules(?int $id = null): array {
        return [];
    }
}
