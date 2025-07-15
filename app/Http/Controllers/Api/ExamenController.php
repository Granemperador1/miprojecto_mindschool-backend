<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Examen;
use App\Models\Curso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponses;

class ExamenController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Examen::with(['curso', 'creador']);

            // Filtros
            if ($request->has('curso_id')) {
                $query->where('curso_id', $request->curso_id);
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            // Si es profesor, solo mostrar exámenes de sus cursos
            if (auth()->user()->hasRole('profesor')) {
                $query->whereHas('curso', function($q) {
                    $q->where('instructor_id', auth()->id());
                });
            }

            $examenes = $query->orderBy('fecha_inicio', 'desc')->paginate(15);

            return $this->successResponse($examenes, 'Exámenes obtenidos exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener exámenes: ' . $e->getMessage(), 500);
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
                'curso_id' => 'required|exists:cursos,id',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio',
                'estado' => 'nullable|in:borrador,activo,inactivo',
                'creador_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            // Verificar que el usuario tenga permisos para crear exámenes en este curso
            if (auth()->user()->hasRole('profesor')) {
                $curso = Curso::find($request->curso_id);
                if (!$curso || $curso->instructor_id !== auth()->id()) {
                    return $this->errorResponse('No tienes permisos para crear exámenes en este curso', 403);
                }
            }

            $examen = Examen::create($request->all());
            $examen->load(['curso', 'creador']);

            return $this->successResponse($examen, 'Examen creado exitosamente', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear examen: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Examen $examen)
    {
        try {
            // Verificar permisos
            if (auth()->user()->hasRole('profesor')) {
                if ($examen->curso->instructor_id !== auth()->id()) {
                    return $this->errorResponse('No tienes permisos para ver este examen', 403);
                }
            }

            $examen->load(['curso', 'creador', 'preguntas']);
            return $this->successResponse($examen, 'Examen obtenido exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener examen: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Examen $examen)
    {
        try {
            $validator = Validator::make($request->all(), [
                'titulo' => 'string|max:255',
                'descripcion' => 'string',
                'fecha_inicio' => 'date',
                'fecha_fin' => 'date|after:fecha_inicio',
                'estado' => 'in:borrador,activo,inactivo'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            // Verificar permisos
            if (auth()->user()->hasRole('profesor')) {
                if ($examen->curso->instructor_id !== auth()->id()) {
                    return $this->errorResponse('No tienes permisos para editar este examen', 403);
                }
            }

            $examen->update($request->all());
            $examen->refresh();
            $examen->load(['curso', 'creador']);

            return $this->successResponse($examen, 'Examen actualizado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar examen: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Examen $examen)
    {
        try {
            // Verificar permisos
            if (auth()->user()->hasRole('profesor')) {
                if ($examen->curso->instructor_id !== auth()->id()) {
                    return $this->errorResponse('No tienes permisos para eliminar este examen', 403);
                }
            }

            $examen->delete();
            return $this->successResponse(null, 'Examen eliminado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar examen: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener exámenes de un curso específico
     */
    public function cursoExamenes($cursoId)
    {
        try {
            $query = Examen::where('curso_id', $cursoId)
                ->with(['creador'])
                ->orderBy('fecha_inicio', 'desc');

            // Si es profesor, verificar que sea su curso
            if (auth()->user()->hasRole('profesor')) {
                $curso = Curso::find($cursoId);
                if (!$curso || $curso->instructor_id !== auth()->id()) {
                    return $this->errorResponse('No tienes permisos para ver exámenes de este curso', 403);
                }
            }

            $examenes = $query->get();
            return $this->successResponse($examenes, 'Exámenes del curso obtenidos exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener exámenes del curso: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener exámenes activos para estudiantes
     */
    public function examenesActivos()
    {
        try {
            $examenes = Examen::where('estado', 'activo')
                ->where('fecha_inicio', '<=', now())
                ->where('fecha_fin', '>=', now())
                ->with(['curso'])
                ->get();

            return $this->successResponse($examenes, 'Exámenes activos obtenidos exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener exámenes activos: ' . $e->getMessage(), 500);
        }
    }
} 