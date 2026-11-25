<?php

namespace App\Modules\Monitoring\Http\Middleware;

use App\Modules\Monitoring\Models\TechnicalEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class RecordRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = hrtime(true);
        $status = 500;
        try {
            $response = $next($request);
            $status = $response->getStatusCode();

            return $response;
        } catch (Throwable $exception) {
            $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
            throw $exception;
        } finally {
            $elapsed = (hrtime(true) - $start) / 1000000;
            if (! $request->is('media/*', 'up', 'build/*')) {
                try {
                    TechnicalEvent::query()->create([
                        'route_name' => $request->route()?->getName() ?? 'unmatched', 'method' => $request->method(),
                        'status' => $status, 'duration_ms' => $elapsed,
                        'verification' => app()->runningUnitTests() || config('monitoring.verification'), 'created_at' => now(),
                    ]);
                } catch (Throwable) {
                    Log::warning('technical_event_not_saved');
                }
            }
        }
    }
}
