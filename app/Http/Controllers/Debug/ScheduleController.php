<?php

declare(strict_types=1);

namespace App\Http\Controllers\Debug;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController
{
    public function index(): Response
    {
        Artisan::call('schedule:list', ['--json' => true]);

        /** @var list<array{expression: string, command: string, description: string|null, next_due_date: string, next_due_date_human: string, timezone: string, has_mutex: bool, repeat_seconds: int|null, environments: list<string>}> $tasks */
        $tasks = json_decode(Artisan::output(), true) ?? [];

        return Inertia::render('debug/schedule/Index', [
            'tasks' => $tasks,
        ]);
    }

    /**
     * Run a scheduled artisan command on demand.
     *
     * Only commands registered in the schedule may be executed — arbitrary
     * command strings are rejected to prevent command injection.
     *
     * @throws ValidationException
     */
    public function run(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'command' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $command = (string) $request->input('command');

        // Resolve all scheduled commands and whitelist against them.
        Artisan::call('schedule:list', ['--json' => true]);

        /** @var list<array{command: string}> $tasks */
        $tasks = json_decode(Artisan::output(), true) ?? [];

        $allowed = array_column($tasks, 'command');

        if (!in_array($command, $allowed, true)) {
            return response()->json(['message' => 'Unbekannter Befehl.'], 422);
        }

        // Strip "php artisan " prefix so Artisan::call() receives the command name.
        $artisanCommand = preg_replace('/^php artisan\s+/', '', $command);

        $exitCode = Artisan::call((string) $artisanCommand);
        $output = Artisan::output();

        return response()->json([
            'output' => $output ?: '(no output)',
            'exit_code' => $exitCode,
        ]);
    }
}
