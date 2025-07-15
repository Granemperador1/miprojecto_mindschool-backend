<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiLoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log request
        $this->logRequest($request);
        
        // Process request
        $response = $next($request);
        
        // Log response
        $this->logResponse($request, $response, $startTime);
        
        return $response;
    }

    /**
     * Log incoming request
     */
    private function logRequest(Request $request): void
    {
        $logData = [
            'type' => 'api_request',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id,
            'timestamp' => now()->toISOString(),
        ];

        // Log sensitive data only in development
        if (app()->environment('local')) {
            $logData['headers'] = $request->headers->all();
            $logData['body'] = $request->all();
        }

        Log::channel('api')->info('API Request', $logData);
    }

    /**
     * Log outgoing response
     */
    private function logResponse(Request $request, Response $response, float $startTime): void
    {
        $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        
        $logData = [
            'type' => 'api_response',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => round($duration, 2),
            'user_id' => $request->user()?->id,
            'timestamp' => now()->toISOString(),
        ];

        // Log response body for errors
        if ($response->getStatusCode() >= 400) {
            $logData['error'] = true;
            
            if (app()->environment('local')) {
                $logData['response_body'] = $response->getContent();
            }
            
            Log::channel('api')->error('API Error Response', $logData);
        } else {
            Log::channel('api')->info('API Response', $logData);
        }

        // Log slow requests
        if ($duration > 1000) { // More than 1 second
            Log::channel('api')->warning('Slow API Request', [
                'duration_ms' => round($duration, 2),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => $request->user()?->id,
            ]);
        }
    }
} 