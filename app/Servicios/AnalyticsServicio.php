<?php

namespace App\Servicios;

use App\Models\User;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Tarea;
use App\Models\EntregaTarea;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Servicio para manejar todas las operaciones de analytics y métricas
 * 
 * Responsabilidades:
 * - Calcular métricas de usuarios
 * - Analizar rendimiento de cursos
 * - Generar reportes de actividad
 * - Gestionar caché de analytics
 */
class AnalyticsServicio
{
    private const TIEMPO_CACHE_DASHBOARD = 300; // 5 minutos
    private const TIEMPO_CACHE_CURSO = 600; // 10 minutos
    private const TIEMPO_CACHE_USUARIO = 300; // 5 minutos

    /**
     * Obtiene el dashboard principal con todas las métricas del sistema
     * 
     * @return array Datos del dashboard con métricas principales
     */
    public function obtenerDashboard(): array
    {
        return Cache::remember('analytics_dashboard', self::TIEMPO_CACHE_DASHBOARD, function () {
            $fechaActual = Carbon::now();
            $fechaMesAnterior = $fechaActual->copy()->subMonth();
            
            return [
                'usuarios' => $this->calcularMetricasUsuarios($fechaMesAnterior),
                'cursos' => $this->calcularMetricasCursos($fechaMesAnterior),
                'inscripciones' => $this->calcularMetricasInscripciones($fechaMesAnterior),
                'tareas' => $this->calcularMetricasTareas(),
                'rendimiento' => $this->calcularMetricasRendimiento()
            ];
        });
    }

    /**
     * Obtiene analytics detallados de un curso específico
     * 
     * @param Curso $curso Curso a analizar
     * @return array Datos de analytics del curso
     */
    public function obtenerAnalyticsCurso(Curso $curso): array
    {
        $claveCache = "analytics_curso_{$curso->id}";
        
        return Cache::remember($claveCache, self::TIEMPO_CACHE_CURSO, function () use ($curso) {
            $inscripciones = $curso->inscripciones;
            $tareas = $curso->tareas;
            
            return [
                'informacion_curso' => $this->obtenerInformacionCurso($curso),
                'metricas_estudiantes' => $this->calcularMetricasEstudiantes($inscripciones),
                'progreso_estudiantes' => $this->calcularProgresoEstudiantes($inscripciones),
                'metricas_tareas' => $this->calcularMetricasTareasCurso($tareas),
                'actividad_reciente' => $this->calcularActividadReciente($curso)
            ];
        });
    }

    /**
     * Obtiene analytics de un usuario específico
     * 
     * @param User $usuario Usuario a analizar
     * @return array Datos de analytics del usuario
     */
    public function obtenerAnalyticsUsuario(User $usuario): array
    {
        $claveCache = "analytics_usuario_{$usuario->id}";
        
        return Cache::remember($claveCache, self::TIEMPO_CACHE_USUARIO, function () use ($usuario) {
            $inscripciones = $usuario->inscripciones;
            $entregas = $usuario->entregasTareas;
            
            return [
                'informacion_usuario' => $this->obtenerInformacionUsuario($usuario),
                'actividad_academica' => $this->calcularActividadAcademica($inscripciones, $entregas),
                'progreso_academico' => $this->calcularProgresoAcademico($inscripciones)
            ];
        });
    }

    /**
     * Calcula métricas de usuarios del sistema
     * 
     * @param Carbon $fechaMesAnterior Fecha del mes anterior para comparaciones
     * @return array Métricas de usuarios
     */
    private function calcularMetricasUsuarios(Carbon $fechaMesAnterior): array
    {
        return [
            'total' => User::count(),
            'nuevos_este_mes' => User::where('created_at', '>=', $fechaMesAnterior)->count(),
            'activos_este_mes' => $this->contarUsuariosActivos($fechaMesAnterior),
            'distribucion_por_rol' => [
                'estudiantes' => User::role('estudiante')->count(),
                'profesores' => User::role('profesor')->count(),
                'administradores' => User::role('admin')->count(),
            ]
        ];
    }

    /**
     * Calcula métricas de cursos del sistema
     * 
     * @param Carbon $fechaMesAnterior Fecha del mes anterior para comparaciones
     * @return array Métricas de cursos
     */
    private function calcularMetricasCursos(Carbon $fechaMesAnterior): array
    {
        $totalCursos = Curso::count();
        $inscripcionesActivas = Inscripcion::where('estado', 'activo')->count();
        
        return [
            'total' => $totalCursos,
            'activos' => Curso::where('estado', 'activo')->count(),
            'nuevos_este_mes' => Curso::where('created_at', '>=', $fechaMesAnterior)->count(),
            'promedio_estudiantes_por_curso' => round($inscripcionesActivas / max($totalCursos, 1), 2)
        ];
    }

    /**
     * Calcula métricas de inscripciones
     * 
     * @param Carbon $fechaMesAnterior Fecha del mes anterior para comparaciones
     * @return array Métricas de inscripciones
     */
    private function calcularMetricasInscripciones(Carbon $fechaMesAnterior): array
    {
        $totalInscripciones = Inscripcion::count();
        $inscripcionesCompletadas = Inscripcion::where('estado', 'completado')->count();
        
        return [
            'total' => $totalInscripciones,
            'nuevas_este_mes' => Inscripcion::where('created_at', '>=', $fechaMesAnterior)->count(),
            'completadas' => $inscripcionesCompletadas,
            'tasa_completacion_porcentual' => round(
                $inscripcionesCompletadas / max($totalInscripciones, 1) * 100, 2
            )
        ];
    }

    /**
     * Calcula métricas de tareas
     * 
     * @return array Métricas de tareas
     */
    private function calcularMetricasTareas(): array
    {
        return [
            'total' => Tarea::count(),
            'entregadas' => EntregaTarea::count(),
            'promedio_calificacion' => round(
                EntregaTarea::whereNotNull('calificacion')->avg('calificacion'), 2
            )
        ];
    }

    /**
     * Calcula métricas de rendimiento del sistema
     * 
     * @return array Métricas de rendimiento
     */
    private function calcularMetricasRendimiento(): array
    {
        return [
            'tiempo_respuesta_promedio_ms' => $this->obtenerTiempoRespuestaPromedio(),
            'usuarios_concurrentes' => $this->obtenerUsuariosConcurrentes(),
            'errores_ultimas_24_horas' => $this->contarErroresRecientes()
        ];
    }

    /**
     * Cuenta usuarios activos en el último mes
     * 
     * @param Carbon $fechaMesAnterior Fecha del mes anterior
     * @return int Número de usuarios activos
     */
    private function contarUsuariosActivos(Carbon $fechaMesAnterior): int
    {
        return User::whereHas('inscripciones', function($consulta) use ($fechaMesAnterior) {
            $consulta->where('updated_at', '>=', $fechaMesAnterior);
        })->count();
    }

    /**
     * Obtiene información básica del curso
     * 
     * @param Curso $curso Curso a analizar
     * @return array Información del curso
     */
    private function obtenerInformacionCurso(Curso $curso): array
    {
        $inscripciones = $curso->inscripciones;
        
        return [
            'id' => $curso->id,
            'titulo' => $curso->titulo,
            'estudiantes_inscritos' => $inscripciones->count(),
            'estudiantes_activos' => $inscripciones->where('estado', 'activo')->count(),
            'estudiantes_completados' => $inscripciones->where('estado', 'completado')->count(),
            'tasa_completacion_porcentual' => round(
                $inscripciones->where('estado', 'completado')->count() / max($inscripciones->count(), 1) * 100, 2
            )
        ];
    }

    /**
     * Calcula métricas de estudiantes de un curso
     * 
     * @param \Illuminate\Database\Eloquent\Collection $inscripciones Inscripciones del curso
     * @return array Métricas de estudiantes
     */
    private function calcularMetricasEstudiantes($inscripciones): array
    {
        return [
            'total_inscritos' => $inscripciones->count(),
            'activos' => $inscripciones->where('estado', 'activo')->count(),
            'completados' => $inscripciones->where('estado', 'completado')->count(),
            'en_progreso' => $inscripciones->where('estado', 'en_progreso')->count()
        ];
    }

    /**
     * Calcula distribución del progreso de estudiantes
     * 
     * @param \Illuminate\Database\Eloquent\Collection $inscripciones Inscripciones del curso
     * @return array Distribución del progreso
     */
    private function calcularProgresoEstudiantes($inscripciones): array
    {
        return [
            'promedio_progreso_porcentual' => round($inscripciones->avg('progreso'), 2),
            'distribucion_progreso' => [
                'inicial_0_25' => $inscripciones->where('progreso', '<=', 25)->count(),
                'basico_26_50' => $inscripciones->whereBetween('progreso', [26, 50])->count(),
                'intermedio_51_75' => $inscripciones->whereBetween('progreso', [51, 75])->count(),
                'avanzado_76_100' => $inscripciones->where('progreso', '>', 75)->count(),
            ]
        ];
    }

    /**
     * Calcula métricas de tareas de un curso
     * 
     * @param \Illuminate\Database\Eloquent\Collection $tareas Tareas del curso
     * @return array Métricas de tareas
     */
    private function calcularMetricasTareasCurso($tareas): array
    {
        $tareasConEntregas = $tareas->withCount('entregas')->get();
        $totalEntregas = $tareasConEntregas->sum('entregas_count');
        
        $promedioCalificacion = $tareas->with('entregas')
            ->get()
            ->flatMap->entregas
            ->whereNotNull('calificacion')
            ->avg('calificacion');

        return [
            'total_tareas' => $tareas->count(),
            'tareas_entregadas' => $totalEntregas,
            'promedio_calificacion' => round($promedioCalificacion, 2)
        ];
    }

    /**
     * Calcula actividad reciente del curso
     * 
     * @param Curso $curso Curso a analizar
     * @return array Actividad reciente
     */
    private function calcularActividadReciente(Curso $curso): array
    {
        $fechaSemanaAnterior = Carbon::now()->subWeek();
        
        return [
            'inscripciones_ultima_semana' => $curso->inscripciones()
                ->where('created_at', '>=', $fechaSemanaAnterior)
                ->count(),
            'entregas_ultima_semana' => $curso->tareas()
                ->with('entregas')
                ->get()
                ->flatMap->entregas
                ->where('created_at', '>=', $fechaSemanaAnterior)
                ->count()
        ];
    }

    /**
     * Obtiene información básica del usuario
     * 
     * @param User $usuario Usuario a analizar
     * @return array Información del usuario
     */
    private function obtenerInformacionUsuario(User $usuario): array
    {
        return [
            'id' => $usuario->id,
            'nombre' => $usuario->name,
            'email' => $usuario->email,
            'roles' => $usuario->roles->pluck('name'),
            'fecha_registro' => $usuario->created_at
        ];
    }

    /**
     * Calcula actividad académica del usuario
     * 
     * @param \Illuminate\Database\Eloquent\Collection $inscripciones Inscripciones del usuario
     * @param \Illuminate\Database\Eloquent\Collection $entregas Entregas del usuario
     * @return array Actividad académica
     */
    private function calcularActividadAcademica($inscripciones, $entregas): array
    {
        return [
            'cursos_inscritos' => $inscripciones->count(),
            'cursos_completados' => $inscripciones->where('estado', 'completado')->count(),
            'tareas_entregadas' => $entregas->count(),
            'promedio_calificacion' => round($entregas->whereNotNull('calificacion')->avg('calificacion'), 2),
            'ultima_actividad' => $inscripciones->max('updated_at')
        ];
    }

    /**
     * Calcula progreso académico del usuario
     * 
     * @param \Illuminate\Database\Eloquent\Collection $inscripciones Inscripciones del usuario
     * @return array Progreso académico
     */
    private function calcularProgresoAcademico($inscripciones): array
    {
        return [
            'promedio_progreso_porcentual' => round($inscripciones->avg('progreso'), 2),
            'cursos_en_progreso' => $inscripciones->where('estado', 'en_progreso')->count(),
            'cursos_activos' => $inscripciones->where('estado', 'activo')->count(),
            'cursos_completados' => $inscripciones->where('estado', 'completado')->count()
        ];
    }

    /**
     * Obtiene tiempo de respuesta promedio del sistema
     * 
     * @return int Tiempo en milisegundos
     */
    private function obtenerTiempoRespuestaPromedio(): int
    {
        // TODO: Implementar métrica real de tiempo de respuesta
        return rand(100, 500);
    }

    /**
     * Obtiene número de usuarios concurrentes
     * 
     * @return int Número de usuarios concurrentes
     */
    private function obtenerUsuariosConcurrentes(): int
    {
        // TODO: Implementar métrica real de usuarios concurrentes
        return rand(10, 100);
    }

    /**
     * Cuenta errores en las últimas 24 horas
     * 
     * @return int Número de errores
     */
    private function contarErroresRecientes(): int
    {
        $archivoLog = storage_path('logs/laravel.log');
        
        if (file_exists($archivoLog)) {
            $contenido = file_get_contents($archivoLog);
            return substr_count($contenido, 'ERROR');
        }
        
        return 0;
    }
} 