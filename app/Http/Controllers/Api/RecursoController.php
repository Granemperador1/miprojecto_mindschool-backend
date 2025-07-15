<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recurso;
use App\Models\Curso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponses;

class RecursoController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Recurso::with(['curso', 'creador']);

            // Filtros
            if ($request->has('curso_id')) {
                $query->where('curso_id', $request->curso_id);
            }

            if ($request->has('tipo')) {
                $query->where('tipo', $request->tipo);
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            // Si es profesor, solo mostrar recursos de sus cursos
            if (auth()->user()->hasRole('profesor')) {
                $query->whereHas('curso', function($q) {
                    $q->where('instructor_id', auth()->id());
                });
            }

            // Si es estudiante, solo mostrar recursos activos de cursos en los que esté inscrito
            if (auth()->user()->hasRole('estudiante')) {
                $query->where('estado', 'activo')
                    ->whereHas('curso.inscripciones', function($q) {
                        $q->where('user_id', auth()->id());
                    });
            }

            $recursos = $query->orderBy('created_at', 'desc')->paginate(15);

            return $this->successResponse($recursos, 'Recursos obtenidos exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener recursos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'titulo' => 'required|string|max:255',
                'descripcion' => 'required|string',
                'tipo' => 'required|in:documento,video,audio,imagen,enlace',
                'url' => 'nullable|url',
                'curso_id' => 'required|exists:cursos,id',
                'estado' => 'nullable|in:activo,inactivo',
                'creador_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            // Verificar que el usuario tenga permisos para crear recursos en este curso
            if (auth()->user()->hasRole('profesor')) {
                $curso = Curso::find($request->curso_id);
                if (!$curso || $curso->instructor_id !== auth()->id()) {
                    return $this->errorResponse('No tienes permisos para crear recursos en este curso', 403);
                }
            }

            $recurso = Recurso::create($request->all());
            $recurso->load(['curso', 'creador']);

            return $this->successResponse($recurso, 'Recurso creado exitosamente', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear recurso: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Recurso $recurso)
    {
        try {
            // Verificar permisos
            if (auth()->user()->hasRole('profesor')) {
                if ($recurso->curso->instructor_id !== auth()->id()) {
                    return $this->errorResponse('No tienes permisos para ver este recurso', 403);
                }
            }

            if (auth()->user()->hasRole('estudiante')) {
                if ($recurso->estado !== 'activo' || !$recurso->curso->inscripciones()->where('user_id', auth()->id())->exists()) {
                    return $this->errorResponse('No tienes permisos para ver este recurso', 403);
                }
            }

            $recurso->load(['curso', 'creador']);
            return $this->successResponse($recurso, 'Recurso obtenido exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener recurso: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Recurso $recurso)
    {
        try {
            $validator = Validator::make($request->all(), [
                'titulo' => 'string|max:255',
                'descripcion' => 'string',
                'tipo' => 'in:documento,video,audio,imagen,enlace',
                'url' => 'nullable|url',
                'estado' => 'in:activo,inactivo'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            // Verificar permisos
            if (auth()->user()->hasRole('profesor')) {
                if ($recurso->curso->instructor_id !== auth()->id()) {
                    return $this->errorResponse('No tienes permisos para editar este recurso', 403);
                }
            }

            $recurso->update($request->all());
            $recurso->refresh();
            $recurso->load(['curso', 'creador']);

            return $this->successResponse($recurso, 'Recurso actualizado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar recurso: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Recurso $recurso)
    {
        try {
            // Verificar permisos
            if (auth()->user()->hasRole('profesor')) {
                if ($recurso->curso->instructor_id !== auth()->id()) {
                    return $this->errorResponse('No tienes permisos para eliminar este recurso', 403);
                }
            }

            $recurso->delete();
            return $this->successResponse(null, 'Recurso eliminado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar recurso: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener recursos de un curso específico
     */
    public function cursoRecursos($cursoId)
    {
        try {
            $query = Recurso::where('curso_id', $cursoId)
                ->with(['creador'])
                ->orderBy('created_at', 'desc');

            // Si es profesor, verificar que sea su curso
            if (auth()->user()->hasRole('profesor')) {
                $curso = Curso::find($cursoId);
                if (!$curso || $curso->instructor_id !== auth()->id()) {
                    return $this->errorResponse('No tienes permisos para ver recursos de este curso', 403);
                }
            }

            // Si es estudiante, solo mostrar recursos activos
            if (auth()->user()->hasRole('estudiante')) {
                $query->where('estado', 'activo');
            }

            $recursos = $query->get();
            return $this->successResponse($recursos, 'Recursos del curso obtenidos exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener recursos del curso: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener recursos por tipo
     */
    public function recursosPorTipo($tipo)
    {
        try {
            $query = Recurso::where('tipo', $tipo)
                ->with(['curso', 'creador'])
                ->orderBy('created_at', 'desc');

            // Si es profesor, solo mostrar recursos de sus cursos
            if (auth()->user()->hasRole('profesor')) {
                $query->whereHas('curso', function($q) {
                    $q->where('instructor_id', auth()->id());
                });
            }

            // Si es estudiante, solo mostrar recursos activos
            if (auth()->user()->hasRole('estudiante')) {
                $query->where('estado', 'activo')
                    ->whereHas('curso.inscripciones', function($q) {
                        $q->where('user_id', auth()->id());
                    });
            }

            $recursos = $query->get();
            return $this->successResponse($recursos, 'Recursos por tipo obtenidos exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener recursos por tipo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Buscar recursos
     */
    public function buscar(Request $request)
    {
        try {
            $query = Recurso::with(['curso', 'creador']);

            if ($request->has('q')) {
                $searchTerm = $request->q;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('titulo', 'like', "%{$searchTerm}%")
                      ->orWhere('descripcion', 'like', "%{$searchTerm}%");
                });
            }

            // Si es profesor, solo mostrar recursos de sus cursos
            if (auth()->user()->hasRole('profesor')) {
                $query->whereHas('curso', function($q) {
                    $q->where('instructor_id', auth()->id());
                });
            }

            // Si es estudiante, solo mostrar recursos activos
            if (auth()->user()->hasRole('estudiante')) {
                $query->where('estado', 'activo')
                    ->whereHas('curso.inscripciones', function($q) {
                        $q->where('user_id', auth()->id());
                    });
            }

            $recursos = $query->orderBy('created_at', 'desc')->paginate(15);
            return $this->successResponse($recursos, 'Búsqueda de recursos completada exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al buscar recursos: ' . $e->getMessage(), 500);
        }
    }
} 