<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Facades\Activity;
use Throwable;

class ActivityLogService
{
    public function logException(Throwable $throwable, array $context = []): void
    {
        $properties = [
            'context' => $context,
            'exception' => [
                'class' => $throwable::class,
                'message' => $throwable->getMessage(),
                'code' => $throwable->getCode(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
            ],
        ];

        if (config('app.debug')) {
            $properties['exception']['trace'] = $throwable->getTraceAsString();
        }

        $this->write(
            logName: 'errors',
            description: $throwable->getMessage() ?: 'Unhandled exception',
            event: 'exception',
            properties: $properties,
        );
    }

    public function logFrontendError(array $payload): void
    {
        $this->write(
            logName: 'frontend',
            description: Arr::get($payload, 'message', 'Frontend error'),
            event: 'frontend-error',
            properties: [
                'error' => $payload,
            ],
        );
    }

    private function write(string $logName, string $description, string $event, array $properties = []): void
    {
        try {
            activity($logName)
                ->event($event)
                ->withProperties($properties)
                ->log($description);
        } catch (Throwable $throwable) {
            Log::error('Activity logging failed.', [
                'log_name' => $logName,
                'event' => $event,
                'description' => $description,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);
        }
    }
}
