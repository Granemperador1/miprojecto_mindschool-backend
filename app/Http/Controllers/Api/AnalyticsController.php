<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Tarea;
use App\Models\EntregaTarea;
use App\Models\AnalyticsCurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function dashboard()
    {
        // Cache por 5 minutos
        return Cache::remember('analytics_dashboard', 300, function () {
            $now = Carbon::now();
            $lastMonth = $now->copy()->subMonth();
            
            return [
                'usuarios' => [
                    'total' => User::count(),
                    'nuevos_este_mes' => User::where('created_at', '>=', $lastMonth)->count(),
                    'activos_este_mes' => User::whereHas('inscripciones', function($q) use ($lastMonth) {
                        $q->where('updated_at', '>=', $lastMonth);
                    })->count(),
                    'por_rol' => [
                        'estudiantes' => User::role('estudiante')->count(),
                        'profesores' => User::role('profesor')->count(),
                        'admins' => User::role('admin')->count(),
                    ]
                ],
                'cursos' => [
                    'total' => Curso::count(),
                    'activos' => Curso::where('estado', 'activo')->count(),
                    'nuevos_este_mes' => Curso::where('created_at', '>=', $lastMonth)->count(),
                    'promedio_estudiantes' => round(Inscripcion::where('estado', 'activo')->count() / max(Curso::count(), 1), 2)
                ],
                'inscripciones' => [
                    'total' => Inscripcion::count(),
                    'este_mes' => Inscripcion::where('created_at', '>=', $lastMonth)->count(),
                    'completadas' => Inscripcion::where('estado', 'completado')->count(),
                    'tasa_completacion' => round(
                        Inscripcion::where('estado', 'completado')->count() / max(Inscripcion::count(), 1) * 100, 2
                    )
                ],
                'tareas' => [
                    'total' => Tarea::count(),
                    'entregadas' => EntregaTarea::count(),
                    'promedio_calificacion' => round(EntregaTarea::whereNotNull('calificacion')->avg('calificacion'), 2)
                ],
                'rendimiento' => [
                    'tiempo_respuesta_promedio' => $this->getAverageResponseTime(),
                    'usuarios_concurrentes' => $this->getConcurrentUsers(),
                    'errores_ultimas_24h' => $this->getErrorCount()
                ]
            ];
        });
    }

    public function cursoAnalytics(Curso $curso)
    {
        $cacheKey = "analytics_curso_{$curso->id}";
        
        return Cache::remember($cacheKey, 600, function () use ($curso) {
            $inscripciones = $curso->inscripciones;
            $tareas = $curso->tareas;
            
            return [
                'curso' => [
                    'id' => $curso->id,
                    'titulo' => $curso->titulo,
                    'estudiantes_inscritos' => $inscripciones->count(),
                    'estudiantes_activos' => $inscripciones->where('estado', 'activo')->count(),
                    'estudiantes_completados' => $inscripciones->where('estado', 'completado')->count(),
                    'tasa_completacion' => round(
                        $inscripciones->where('estado', 'completado')->count() / max($inscripciones->count(), 1) * 100, 2
                    )
                ],
                'progreso' => [
                    'promedio_progreso' => round($inscripciones->avg('progreso'), 2),
                    'distribucion_progreso' => [
                        '0-25%' => $inscripciones->where('progreso', '<=', 25)->count(),
                        '26-50%' => $inscripciones->whereBetween('progreso', [26, 50])->count(),
                        '51-75%' => $inscripciones->whereBetween('progreso', [51, 75])->count(),
                        '76-100%' => $inscripciones->where('progreso', '>', 75)->count(),
                    ]
                ],
                'tareas' => [
                    'total_tareas' => $tareas->count(),
                    'tareas_entregadas' => $tareas->withCount('entregas')->get()->sum('entregas_count'),
                    'promedio_calificacion' => round(
                        $tareas->with('entregas')->get()->flatMap->entregas->whereNotNull('calificacion')->avg('calificacion'), 2
                    )
                ],
                'actividad_reciente' => [
                    'inscripciones_ultima_semana' => $inscripciones->where('created_at', '>=', Carbon::now()->subWeek())->count(),
                    'entregas_ultima_semana' => $tareas->with('entregas')->get()->flatMap->entregas->where('created_at', '>=', Carbon::now()->subWeek())->count()
                ]
            ];
        });
    }

    public function userAnalytics(User $user)
    {
        $cacheKey = "analytics_user_{$user->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($user) {
            $inscripciones = $user->inscripciones;
            $entregas = $user->entregasTareas;
            
            return [
                'usuario' => [
                    'id' => $user->id,
                    'nombre' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                    'fecha_registro' => $user->created_at
                ],
                'actividad' => [
                    'cursos_inscritos' => $inscripciones->count(),
                    'cursos_completados' => $inscripciones->where('estado', 'completado')->count(),
                    'tareas_entregadas' => $entregas->count(),
                    'promedio_calificacion' => round($entregas->whereNotNull('calificacion')->avg('calificacion'), 2),
                    'ultima_actividad' => $user->updated_at
                ],
                'progreso' => [
                    'promedio_progreso' => round($inscripciones->avg('progreso'), 2),
                    'cursos_en_progreso' => $inscripciones->where('estado', 'en_progreso')->count(),
                    'cursos_activos' => $inscripciones->where('estado', 'activo')->count()
                ]
            ];
        });
    }

    private function getAverageResponseTime()
    {
        // Simular métrica de tiempo de respuesta
        return rand(100, 500); // ms
    }

    private function getConcurrentUsers()
    {
        // Simular usuarios concurrentes
        return rand(10, 100);
    }

    private function getErrorCount()
    {
        // Contar errores en logs
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            return substr_count($content, 'ERROR');
        }
        return 0;
    }
} 