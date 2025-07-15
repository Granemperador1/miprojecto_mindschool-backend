<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;

class LoggingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configurar logging estructurado para API
        Log::channel('api')->pushHandler(
            (new StreamHandler(storage_path('logs/api.log')))
                ->setFormatter(new JsonFormatter())
        );

        // Configurar logging de errores críticos
        Log::channel('critical')->pushHandler(
            (new StreamHandler(storage_path('logs/critical.log')))
                ->setFormatter(new JsonFormatter())
        );

        // Configurar logging de auditoría
        Log::channel('audit')->pushHandler(
            (new StreamHandler(storage_path('logs/audit.log')))
                ->setFormatter(new JsonFormatter())
        );
    }
} 