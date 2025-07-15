<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración de Logging por Defecto
    |--------------------------------------------------------------------------
    |
    | Esta opción controla el canal de logging por defecto que se utiliza
    | cuando se escribe un mensaje de log. El nombre especificado en esta
    | opción debe coincidir con uno de los canales definidos en la
    | configuración de "channels".
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Canales de Logging
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar los canales de logging para tu aplicación.
    | Laravel viene con canales predefinidos que funcionan bien para
    | la mayoría de aplicaciones. Puedes agregar canales personalizados
    | según tus necesidades específicas.
    |
    | Los canales disponibles: "stack", "single", "daily", "slack",
    | "papertrail", "stderr", "syslog", "errorlog", "monolog",
    | "custom", "array"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single', 'daily', 'api'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Canales Personalizados para MindSchool
        |--------------------------------------------------------------------------
        |
        | Canales específicos para diferentes tipos de logs en la aplicación
        | de MindSchool.
        |
        */

        // Canal para logs de API
        'api' => [
            'driver' => 'daily',
            'path' => storage_path('logs/api.log'),
            'level' => 'info',
            'days' => 30,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
            ],
        ],

        // Canal para logs de auditoría
        'audit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/audit.log'),
            'level' => 'info',
            'days' => 90,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
            ],
        ],

        // Canal para logs críticos
        'critical' => [
            'driver' => 'daily',
            'path' => storage_path('logs/critical.log'),
            'level' => 'critical',
            'days' => 365,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
            ],
        ],

        // Canal para logs de seguridad
        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => 'warning',
            'days' => 180,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
            ],
        ],

        // Canal para logs de rendimiento
        'performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/performance.log'),
            'level' => 'info',
            'days' => 30,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
            ],
        ],

        // Canal para logs de errores de base de datos
        'database' => [
            'driver' => 'daily',
            'path' => storage_path('logs/database.log'),
            'level' => 'error',
            'days' => 60,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
            ],
        ],

        // Canal para logs de autenticación
        'auth' => [
            'driver' => 'daily',
            'path' => storage_path('logs/auth.log'),
            'level' => 'info',
            'days' => 90,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
            ],
        ],

        // Canal para logs de transacciones
        'transactions' => [
            'driver' => 'daily',
            'path' => storage_path('logs/transactions.log'),
            'level' => 'info',
            'days' => 180,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
            ],
        ],

        // Canal para logs de notificaciones
        'notifications' => [
            'driver' => 'daily',
            'path' => storage_path('logs/notifications.log'),
            'level' => 'info',
            'days' => 30,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
            ],
        ],

        // Canal para logs de archivos
        'files' => [
            'driver' => 'daily',
            'path' => storage_path('logs/files.log'),
            'level' => 'info',
            'days' => 60,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
            ],
        ],

        // Canal para logs de caché
        'cache' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cache.log'),
            'level' => 'debug',
            'days' => 30,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
            ],
        ],

        // Canal para logs de jobs/colas
        'jobs' => [
            'driver' => 'daily',
            'path' => storage_path('logs/jobs.log'),
            'level' => 'info',
            'days' => 30,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
            ],
        ],

        // Canal para logs de websockets
        'websockets' => [
            'driver' => 'daily',
            'path' => storage_path('logs/websockets.log'),
            'level' => 'info',
            'days' => 30,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
            ],
        ],

        // Canal para logs de analytics
        'analytics' => [
            'driver' => 'daily',
            'path' => storage_path('logs/analytics.log'),
            'level' => 'info',
            'days' => 30,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
            ],
        ],
    ],

];
