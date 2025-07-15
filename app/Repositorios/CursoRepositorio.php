<?php

namespace App\Repositorios;

use App\Models\Curso;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Repositorio para manejar todas las operaciones de base de datos relacionadas con cursos
 * 
 * Responsabilidades:
 * - Consultas optimizadas de cursos
 * - Gestión de caché para consultas frecuentes
 * - Filtrado y búsqueda de cursos
 * - Operaciones CRUD optimizadas
 */
class CursoRepositorio
{
    private const TIEMPO_CACHE_LISTADO = 900; // 15 minutos
    private const TIEMPO_CACHE_DETALLE = 1800; // 30 minutos
    private const ELEMENTOS_POR_PAGINA = 12;

    /**
     * Obtiene listado paginado de cursos con filtros
     * 
     * @param array $filtros Filtros a aplicar
     * @param string $orden Campo por el cual ordenar
     * @param string $direccion Dirección del ordenamiento (asc/desc)
     * @return LengthAwarePaginator Listado paginado de cursos
     */
    public function obtenerListadoConFiltros(array $filtros = [], string $orden = 'created_at', string $direccion = 'desc'): LengthAwarePaginator
    {
        $claveCache = $this->generarClaveCacheListado($filtros, $orden, $direccion);
        
        return Cache::remember($claveCache, self::TIEMPO_CACHE_LISTADO, function () use ($filtros, $orden, $direccion) {
            $consulta = $this->construirConsultaBase();
            
            // Aplicar filtros
            $this->aplicarFiltros($consulta, $filtros);
            
            // Aplicar ordenamiento
            $consulta->orderBy($orden, $direccion);
            
            return $consulta->paginate(self::ELEMENTOS_POR_PAGINA);
        });
    }

    /**
     * Obtiene un curso específico con todas sus relaciones
     * 
     * @param int $idCurso ID del curso
     * @return Curso|null Curso con relaciones cargadas
     */
    public function obtenerConRelaciones(int $idCurso): ?Curso
    {
        $claveCache = "curso_detalle_{$idCurso}";
        
        return Cache::remember($claveCache, self::TIEMPO_CACHE_DETALLE, function () use ($idCurso) {
            return Curso::with([
                'instructor:id,name,email',
                'lecciones:id,curso_id,titulo,descripcion,duracion,orden',
                'inscripciones:id,curso_id,user_id,estado,progreso',
                'tareas:id,curso_id,titulo,descripcion,fecha_entrega'
            ])->find($idCurso);
        });
    }

    /**
     * Obtiene cursos por instructor
     * 
     * @param int $idInstructor ID del instructor
     * @param array $filtros Filtros adicionales
     * @return Collection Colección de cursos del instructor
     */
    public function obtenerPorInstructor(int $idInstructor, array $filtros = []): Collection
    {
        $claveCache = "cursos_instructor_{$idInstructor}_" . md5(serialize($filtros));
        
        return Cache::remember($claveCache, self::TIEMPO_CACHE_LISTADO, function () use ($idInstructor, $filtros) {
            $consulta = $this->construirConsultaBase()
                ->where('instructor_id', $idInstructor);
            
            $this->aplicarFiltros($consulta, $filtros);
            
            return $consulta->get();
        });
    }

    /**
     * Obtiene cursos populares (con más inscripciones)
     * 
     * @param int $limite Número de cursos a obtener
     * @return Collection Cursos más populares
     */
    public function obtenerCursosPopulares(int $limite = 10): Collection
    {
        $claveCache = "cursos_populares_{$limite}";
        
        return Cache::remember($claveCache, self::TIEMPO_CACHE_LISTADO, function () use ($limite) {
            return Curso::withCount('inscripciones')
                ->where('estado', 'activo')
                ->orderBy('inscripciones_count', 'desc')
                ->limit($limite)
                ->get();
        });
    }

    /**
     * Busca cursos por término de búsqueda
     * 
     * @param string $termino Término de búsqueda
     * @param array $filtros Filtros adicionales
     * @return LengthAwarePaginator Resultados de búsqueda paginados
     */
    public function buscarPorTermino(string $termino, array $filtros = []): LengthAwarePaginator
    {
        $claveCache = "busqueda_cursos_" . md5($termino . serialize($filtros));
        
        return Cache::remember($claveCache, self::TIEMPO_CACHE_LISTADO, function () use ($termino, $filtros) {
            $consulta = $this->construirConsultaBase()
                ->where(function ($query) use ($termino) {
                    $query->where('titulo', 'LIKE', "%{$termino}%")
                          ->orWhere('descripcion', 'LIKE', "%{$termino}%");
                });
            
            $this->aplicarFiltros($consulta, $filtros);
            
            return $consulta->paginate(self::ELEMENTOS_POR_PAGINA);
        });
    }

    /**
     * Obtiene estadísticas básicas de cursos
     * 
     * @return array Estadísticas de cursos
     */
    public function obtenerEstadisticas(): array
    {
        $claveCache = 'estadisticas_cursos';
        
        return Cache::remember($claveCache, self::TIEMPO_CACHE_LISTADO, function () {
            return [
                'total_cursos' => Curso::count(),
                'cursos_activos' => Curso::where('estado', 'activo')->count(),
                'cursos_inactivos' => Curso::where('estado', 'inactivo')->count(),
                'cursos_borrador' => Curso::where('estado', 'borrador')->count(),
                'promedio_estudiantes' => round(
                    Curso::withCount('inscripciones')->get()->avg('inscripciones_count'), 2
                ),
                'cursos_sin_estudiantes' => Curso::doesntHave('inscripciones')->count()
            ];
        });
    }

    /**
     * Crea un nuevo curso
     * 
     * @param array $datos Datos del curso
     * @return Curso Curso creado
     */
    public function crear(array $datos): Curso
    {
        $curso = Curso::create($datos);
        
        // Limpiar caché relacionado
        $this->limpiarCacheListado();
        
        return $curso;
    }

    /**
     * Actualiza un curso existente
     * 
     * @param int $idCurso ID del curso
     * @param array $datos Datos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function actualizar(int $idCurso, array $datos): bool
    {
        $curso = Curso::find($idCurso);
        
        if (!$curso) {
            return false;
        }
        
        $actualizado = $curso->update($datos);
        
        if ($actualizado) {
            // Limpiar caché relacionado
            $this->limpiarCacheCurso($idCurso);
            $this->limpiarCacheListado();
        }
        
        return $actualizado;
    }

    /**
     * Elimina un curso
     * 
     * @param int $idCurso ID del curso
     * @return bool True si se eliminó correctamente
     */
    public function eliminar(int $idCurso): bool
    {
        $curso = Curso::find($idCurso);
        
        if (!$curso) {
            return false;
        }
        
        $eliminado = $curso->delete();
        
        if ($eliminado) {
            // Limpiar caché relacionado
            $this->limpiarCacheCurso($idCurso);
            $this->limpiarCacheListado();
        }
        
        return $eliminado;
    }

    /**
     * Construye la consulta base optimizada
     * 
     * @return \Illuminate\Database\Eloquent\Builder Consulta base
     */
    private function construirConsultaBase()
    {
        return Curso::with(['instructor:id,name', 'lecciones:id,curso_id,titulo'])
            ->select(['id', 'titulo', 'descripcion', 'duracion', 'nivel', 'precio', 'estado', 'instructor_id', 'created_at'])
            ->where('estado', 'activo');
    }

    /**
     * Aplica filtros a la consulta
     * 
     * @param \Illuminate\Database\Eloquent\Builder $consulta Consulta a filtrar
     * @param array $filtros Filtros a aplicar
     */
    private function aplicarFiltros($consulta, array $filtros): void
    {
        // Filtro por nivel
        if (isset($filtros['nivel']) && !empty($filtros['nivel'])) {
            $consulta->where('nivel', $filtros['nivel']);
        }

        // Filtro por instructor
        if (isset($filtros['instructor_id']) && !empty($filtros['instructor_id'])) {
            $consulta->where('instructor_id', $filtros['instructor_id']);
        }

        // Filtro por precio mínimo
        if (isset($filtros['precio_minimo']) && !empty($filtros['precio_minimo'])) {
            $consulta->where('precio', '>=', $filtros['precio_minimo']);
        }

        // Filtro por precio máximo
        if (isset($filtros['precio_maximo']) && !empty($filtros['precio_maximo'])) {
            $consulta->where('precio', '<=', $filtros['precio_maximo']);
        }

        // Filtro por duración mínima
        if (isset($filtros['duracion_minima']) && !empty($filtros['duracion_minima'])) {
            $consulta->where('duracion', '>=', $filtros['duracion_minima']);
        }

        // Filtro por duración máxima
        if (isset($filtros['duracion_maxima']) && !empty($filtros['duracion_maxima'])) {
            $consulta->where('duracion', '<=', $filtros['duracion_maxima']);
        }

        // Filtro por fecha de creación
        if (isset($filtros['fecha_desde']) && !empty($filtros['fecha_desde'])) {
            $consulta->where('created_at', '>=', $filtros['fecha_desde']);
        }

        if (isset($filtros['fecha_hasta']) && !empty($filtros['fecha_hasta'])) {
            $consulta->where('created_at', '<=', $filtros['fecha_hasta']);
        }
    }

    /**
     * Genera clave de caché para el listado
     * 
     * @param array $filtros Filtros aplicados
     * @param string $orden Campo de ordenamiento
     * @param string $direccion Dirección del ordenamiento
     * @return string Clave de caché
     */
    private function generarClaveCacheListado(array $filtros, string $orden, string $direccion): string
    {
        return 'cursos_listado_' . md5(serialize($filtros) . $orden . $direccion);
    }

    /**
     * Limpia caché del listado de cursos
     */
    private function limpiarCacheListado(): void
    {
        // Limpiar todas las claves de caché que empiecen con 'cursos_listado_'
        $claves = [
            'cursos_listado_*',
            'cursos_populares_*',
            'cursos_instructor_*',
            'busqueda_cursos_*',
            'estadisticas_cursos'
        ];
        
        foreach ($claves as $patron) {
            Cache::flush();
        }
    }

    /**
     * Limpia caché de un curso específico
     * 
     * @param int $idCurso ID del curso
     */
    private function limpiarCacheCurso(int $idCurso): void
    {
        Cache::forget("curso_detalle_{$idCurso}");
    }
} 