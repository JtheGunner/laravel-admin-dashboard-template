<?php

declare(strict_types=1);

namespace App\Http\Controllers\Debug;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QueueController
{
    public function index(): Response
    {
        $pendingJobs = DB::table('jobs')
            ->orderBy('available_at')
            ->get()
            ->map(static function (object $row): array {
                /** @var array{displayName?: string, job?: string} $payload */
                $payload = json_decode((string) $row->payload, true) ?? [];

                return [
                    'id' => $row->id,
                    'queue' => $row->queue,
                    'display_name' => $payload['displayName'] ?? class_basename($payload['job'] ?? 'Unknown'),
                    'attempts' => (int) $row->attempts,
                    'reserved_at' => $row->reserved_at,
                    'available_at' => (int) $row->available_at,
                    'created_at' => (int) $row->created_at,
                ];
            })
            ->all();

        $failedJobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(100)
            ->get()
            ->map(static function (object $row): array {
                /** @var array{displayName?: string} $payload */
                $payload = json_decode((string) $row->payload, true) ?? [];
                $exception = (string) $row->exception;
                $firstLine = explode("\n", $exception)[0];

                return [
                    'id' => $row->id,
                    'uuid' => $row->uuid,
                    'queue' => $row->queue,
                    'display_name' => $payload['displayName'] ?? 'Unknown',
                    'exception_short' => $firstLine,
                    'failed_at' => $row->failed_at,
                ];
            })
            ->all();

        return Inertia::render('debug/queue/Index', [
            'pendingJobs' => $pendingJobs,
            'failedJobs' => $failedJobs,
        ]);
    }
}
