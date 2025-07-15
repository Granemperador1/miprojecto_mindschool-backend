<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Curso;
use App\Models\Tarea;
use App\Models\EntregaTarea;
use App\Models\Inscripcion;
use App\Models\Leccion;
use App\Traits\ApiResponses;

class EstudianteController extends Controller
{
    use ApiResponses;

    /**
     * Obtener el dashboard del estudiante
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        // Obtener cursos inscritos del estudiante
        $inscripciones = Inscripcion::where('user_id', $user->id)
            ->with(['curso', 'curso.instructor'])
            ->get();

        // Calcular estadísticas
        $totalCursos = $inscripciones->count();
        $cursosCompletados = $inscripciones->where('progreso', 100)->count();
        $tareasPendientes = $this->getTareasPendientes($user->id)->count();
        $promedioGeneral = $this->calcularPromedioGeneral($user->id);

        return $this->successResponse([
            'estadisticas' => [
                'total_cursos' => $totalCursos,
                'cursos_completados' => $cursosCompletados,
                'tareas_pendientes' => $tareasPendientes,
                'promedio_general' => $promedioGeneral
            ],
            'cursos_recientes' => $inscripciones->take(5)
        ]);
    }

    /**
     * Obtener materias (cursos) del estudiante
     */
    public function misMaterias()
    {
        $user = Auth::user();
        
        $materias = Inscripcion::where('user_id', $user->id)
            ->with(['curso', 'curso.instructor'])
            ->get()
            ->map(function ($inscripcion) use ($user) {
                $curso = $inscripcion->curso;
                
                // Calcular progreso del curso
                $leccionesTotales = Leccion::where('curso_id', $curso->id)->count();
                $leccionesCompletadas = $this->getLeccionesCompletadas($user->id, $curso->id);
                $progreso = $leccionesTotales > 0 ? round(($leccionesCompletadas / $leccionesTotales) * 100) : 0;
                
                // Obtener tareas pendientes
                $tareasPendientes = Tarea::where('curso_id', $curso->id)
                    ->whereDoesntHave('entregas', function ($query) use ($user) {
                        $query->where('estudiante_id', $user->id);
                    })
                    ->where('fecha_entrega', '>', now())
                    ->count();

                // Calcular calificación promedio
                $calificacion = $this->calcularCalificacionCurso($user->id, $curso->id);

                return [
                    'id' => $curso->id,
                    'nombre' => $curso->titulo,
                    'descripcion' => $curso->descripcion,
                    'imagen' => $curso->imagen_url ?? 'https://images.unsplash.com/photo-1547658719-da2b51169166?q=80&w=2080',
                    'profesor' => $curso->instructor ? $curso->instructor->name : 'Sin asignar',
                    'progreso' => $progreso,
                    'calificacion' => $calificacion,
                    'tareas_pendientes' => $tareasPendientes,
                    'estado' => $inscripcion->estado
                ];
            });

        return $this->successResponse($materias);
    }

    /**
     * Obtener tareas pendientes del estudiante
     */
    public function tareasPendientes(Request $request)
    {
        $user = Auth::user();
        
        $query = Tarea::whereDoesntHave('entregas', function ($query) use ($user) {
                $query->where('estudiante_id', $user->id);
            })
            ->whereHas('curso.inscripciones', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['curso', 'curso.instructor']);

        // Filtros
        if ($request->has('curso_id')) {
            $query->where('curso_id', $request->curso_id);
        }

        if ($request->has('estado')) {
            switch ($request->estado) {
                case 'pendientes':
                    $query->where('fecha_entrega', '>', now());
                    break;
                case 'urgentes':
                    $query->where('fecha_entrega', '<=', now()->addDays(3));
                    break;
                case 'vencidas':
                    $query->where('fecha_entrega', '<', now());
                    break;
            }
        }

        $tareas = $query->get()->map(function ($tarea) {
            $diasRestantes = now()->diffInDays($tarea->fecha_entrega, false);
            
            // Determinar prioridad
            $prioridad = 'baja';
            if ($diasRestantes <= 1) $prioridad = 'alta';
            elseif ($diasRestantes <= 3) $prioridad = 'media';

            // Determinar estado
            $estado = 'pendiente';
            if ($diasRestantes < 0) $estado = 'vencida';

            return [
                'id' => $tarea->id,
                'titulo' => $tarea->titulo,
                'descripcion' => $tarea->descripcion,
                'fecha_entrega' => $tarea->fecha_entrega->format('d M Y'),
                'fecha_entrega_iso' => $tarea->fecha_entrega->toISOString(),
                'prioridad' => $prioridad,
                'estado' => $estado,
                'tipo' => $tarea->tipo,
                'puntos_maximos' => $tarea->puntos_maximos,
                'materia' => [
                    'id' => $tarea->curso->id,
                    'nombre' => $tarea->curso->titulo,
                    'profesor' => $tarea->curso->instructor ? $tarea->curso->instructor->name : 'Sin asignar',
                    'icono' => $this->getIconoMateria($tarea->curso->titulo)
                ]
            ];
        });

        return $this->successResponse($tareas);
    }

    /**
     * Obtener detalles de una tarea específica
     */
    public function detalleTarea($tareaId)
    {
        $user = Auth::user();
        
        $tarea = Tarea::whereHas('curso.inscripciones', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['curso', 'curso.instructor'])
            ->findOrFail($tareaId);

        // Verificar si ya entregó la tarea
        $entrega = EntregaTarea::where('tarea_id', $tareaId)
            ->where('estudiante_id', $user->id)
            ->first();

        $diasRestantes = now()->diffInDays($tarea->fecha_entrega, false);
        
        $prioridad = 'baja';
        if ($diasRestantes <= 1) $prioridad = 'alta';
        elseif ($diasRestantes <= 3) $prioridad = 'media';

        $estado = 'pendiente';
        if ($entrega) {
            $estado = $entrega->estado;
        } elseif ($diasRestantes < 0) {
            $estado = 'vencida';
        }

        return $this->successResponse([
            'id' => $tarea->id,
            'titulo' => $tarea->titulo,
            'descripcion' => $tarea->descripcion,
            'fecha_asignacion' => $tarea->fecha_asignacion->format('d M Y'),
            'fecha_entrega' => $tarea->fecha_entrega->format('d M Y'),
            'fecha_entrega_iso' => $tarea->fecha_entrega->toISOString(),
            'prioridad' => $prioridad,
            'estado' => $estado,
            'tipo' => $tarea->tipo,
            'puntos_maximos' => $tarea->puntos_maximos,
            'archivo_url' => $tarea->archivo_url,
            'materia' => [
                'id' => $tarea->curso->id,
                'nombre' => $tarea->curso->titulo,
                'profesor' => $tarea->curso->instructor ? $tarea->curso->instructor->name : 'Sin asignar'
            ],
            'entrega' => $entrega ? [
                'id' => $entrega->id,
                'archivo_url' => $entrega->archivo_url,
                'comentarios' => $entrega->comentarios,
                'calificacion' => $entrega->calificacion,
                'comentarios_profesor' => $entrega->comentarios_profesor,
                'fecha_entrega' => $entrega->fecha_entrega->format('d M Y'),
                'estado' => $entrega->estado
            ] : null
        ]);
    }

    /**
     * Entregar una tarea
     */
    public function entregarTarea(Request $request, $tareaId)
    {
        $user = Auth::user();
        
        // Verificar que el estudiante esté inscrito en el curso
        $tarea = Tarea::whereHas('curso.inscripciones', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->findOrFail($tareaId);

        // Verificar que no haya entregado ya la tarea
        $entregaExistente = EntregaTarea::where('tarea_id', $tareaId)
            ->where('estudiante_id', $user->id)
            ->first();

        if ($entregaExistente) {
            return $this->errorResponse('Ya has entregado esta tarea', 400);
        }

        $request->validate([
            'archivo' => 'required|file|max:1048576', // 1GB máximo
            'comentarios' => 'nullable|string|max:1000'
        ]);

        // Subir archivo
        $archivoPath = $request->file('archivo')->store('entregas', 'public');

        // Crear entrega
        $entrega = EntregaTarea::create([
            'tarea_id' => $tareaId,
            'estudiante_id' => $user->id,
            'archivo_url' => $archivoPath,
            'comentarios' => $request->comentarios,
            'fecha_entrega' => now(),
            'estado' => 'entregada'
        ]);

        return $this->successResponse([
            'mensaje' => 'Tarea entregada exitosamente',
            'entrega' => $entrega
        ]);
    }

    /**
     * Obtener calificaciones del estudiante
     */
    public function calificaciones()
    {
        $user = Auth::user();
        
        $calificaciones = EntregaTarea::where('estudiante_id', $user->id)
            ->whereNotNull('calificacion')
            ->with(['tarea.curso'])
            ->get()
            ->groupBy('tarea.curso_id')
            ->map(function ($entregas, $cursoId) {
                $curso = $entregas->first()->tarea->curso;
                $promedio = $entregas->avg('calificacion');
                
                return [
                    'curso_id' => $cursoId,
                    'curso_nombre' => $curso->titulo,
                    'promedio' => round($promedio, 2),
                    'total_tareas' => $entregas->count(),
                    'calificaciones' => $entregas->map(function ($entrega) {
                        return [
                            'tarea' => $entrega->tarea->titulo,
                            'calificacion' => $entrega->calificacion,
                            'fecha' => $entrega->fecha_entrega->format('d M Y'),
                            'comentarios_profesor' => $entrega->comentarios_profesor
                        ];
                    })
                ];
            });

        return $this->successResponse($calificaciones);
    }

    /**
     * Actualizar perfil del estudiante
     */
    public function actualizarPerfil(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'telefono' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048'
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email
        ];

        // Actualizar avatar si se proporciona
        if ($request->hasFile('avatar')) {
            // Eliminar avatar anterior si existe
            if ($user->avatar_url) {
                Storage::disk('public')->delete($user->avatar_url);
            }
            
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $data['avatar_url'] = $avatarPath;
        }

        $user->update($data);

        return $this->successResponse([
            'mensaje' => 'Perfil actualizado exitosamente',
            'usuario' => $user->fresh()
        ]);
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarContrasena(Request $request)
    {
        $request->validate([
            'password_actual' => 'required|string',
            'password_nuevo' => 'required|string|min:8|confirmed'
        ]);

        $user = Auth::user();

        if (!Hash::check($request->password_actual, $user->password)) {
            return $this->errorResponse('La contraseña actual es incorrecta', 400);
        }

        $user->update([
            'password' => Hash::make($request->password_nuevo)
        ]);

        return $this->successResponse(['mensaje' => 'Contraseña actualizada exitosamente']);
    }

    /**
     * Obtener perfil del estudiante
     */
    public function perfil()
    {
        $user = Auth::user();
        
        // Obtener información adicional del estudiante
        $inscripciones = Inscripcion::where('user_id', $user->id)
            ->with('curso')
            ->get();

        $totalCursos = $inscripciones->count();
        $promedioGeneral = $this->calcularPromedioGeneral($user->id);

        return $this->successResponse([
            'usuario' => $user,
            'estadisticas' => [
                'total_cursos' => $totalCursos,
                'promedio_general' => $promedioGeneral
            ]
        ]);
    }

    // Métodos auxiliares privados

    private function getTareasPendientes($userId)
    {
        return Tarea::whereDoesntHave('entregas', function ($query) use ($userId) {
                $query->where('estudiante_id', $userId);
            })
            ->whereHas('curso.inscripciones', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('fecha_entrega', '>', now());
    }

    private function getLeccionesCompletadas($userId, $cursoId)
    {
        // Aquí implementarías la lógica para contar lecciones completadas
        // Por ahora retornamos un valor de ejemplo
        return rand(5, 15);
    }

    private function calcularCalificacionCurso($userId, $cursoId)
    {
        $entregas = EntregaTarea::whereHas('tarea', function ($query) use ($cursoId) {
                $query->where('curso_id', $cursoId);
            })
            ->where('estudiante_id', $userId)
            ->whereNotNull('calificacion');

        if ($entregas->count() === 0) {
            return null;
        }

        return round($entregas->avg('calificacion'), 2);
    }

    private function calcularPromedioGeneral($userId)
    {
        $entregas = EntregaTarea::where('estudiante_id', $userId)
            ->whereNotNull('calificacion');

        if ($entregas->count() === 0) {
            return null;
        }

        return round($entregas->avg('calificacion'), 2);
    }

    private function getIconoMateria($nombreMateria)
    {
        $iconos = [
            'matemáticas' => 'fas fa-calculator',
            'biología' => 'fas fa-dna',
            'física' => 'fas fa-atom',
            'historia' => 'fas fa-landmark',
            'literatura' => 'fas fa-book',
            'inglés' => 'fas fa-language',
            'programación' => 'fas fa-code',
            'redes' => 'fas fa-network-wired',
            'diseño' => 'fas fa-palette',
            'administración' => 'fas fa-chart-line'
        ];

        foreach ($iconos as $palabra => $icono) {
            if (stripos($nombreMateria, $palabra) !== false) {
                return $icono;
            }
        }

        return 'fas fa-graduation-cap'; // Icono por defecto
    }
} 