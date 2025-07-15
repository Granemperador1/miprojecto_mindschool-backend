<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Calificacion;
use App\Models\Curso;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponses;

class CalificacionController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Calificacion::with(['estudiante', 'curso', 'leccion', 'evaluador']);

            // Filtros
            if ($request->has('estudiante_id')) {
                $query->where('estudiante_id', $request->estudiante_id);
            }

            if ($request->has('curso_id')) {
                $query->where('curso_id', $request->curso_id);
            }

            if ($request->has('tipo_evaluacion')) {
                $query->where('tipo_evaluacion', $request->tipo_evaluacion);
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            $calificaciones = $query->orderBy('fecha_evaluacion', 'desc')->paginate(15);

            return $this->successResponse($calificaciones, 'Calificaciones obtenidas exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener calificaciones: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'estudiante_id' => 'required|exists:users,id',
                'curso_id' => 'required|exists:cursos,id',
                'leccion_id' => 'nullable|exists:lecciones,id',
                'tipo_evaluacion' => 'required|in:tarea,examen,proyecto,participacion,quiz,trabajo_final',
                'calificacion' => 'required|numeric|min:0|max:100',
                'peso' => 'required|numeric|min:0|max:1',
                'comentarios' => 'nullable|string',
                'estado' => 'nullable|in:borrador,publicada,revisada'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $calificacion = Calificacion::create([
                'estudiante_id' => $request->estudiante_id,
                'curso_id' => $request->curso_id,
                'leccion_id' => $request->leccion_id,
                'tipo_evaluacion' => $request->tipo_evaluacion,
                'calificacion' => $request->calificacion,
                'peso' => $request->peso,
                'comentarios' => $request->comentarios,
                'evaluador_id' => auth()->id(),
                'estado' => $request->estado ?? 'borrador'
            ]);

            $calificacion->load(['estudiante', 'curso', 'leccion', 'evaluador']);

            return $this->successResponse($calificacion, 'Calificación creada exitosamente', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear calificación: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Calificacion $calificacion)
    {
        try {
            $calificacion->load(['estudiante', 'curso', 'leccion', 'evaluador']);
            return $this->successResponse($calificacion, 'Calificación obtenida exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener calificación: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Calificacion $calificacion)
    {
        try {
            $validator = Validator::make($request->all(), [
                'calificacion' => 'nullable|numeric|min:0|max:100',
                'peso' => 'nullable|numeric|min:0|max:1',
                'comentarios' => 'nullable|string',
                'estado' => 'nullable|in:borrador,publicada,revisada'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $calificacion->update($request->only(['calificacion', 'peso', 'comentarios', 'estado']));
            $calificacion->refresh();

            return $this->successResponse($calificacion, 'Calificación actualizada exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar calificación: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Calificacion $calificacion)
    {
        try {
            $calificacion->delete();
            return $this->successResponse(null, 'Calificación eliminada exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar calificación: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener calificaciones de un estudiante específico
     */
    public function calificacionesEstudiante($estudianteId)
    {
        try {
            $calificaciones = Calificacion::with(['curso', 'leccion', 'evaluador'])
                ->where('estudiante_id', $estudianteId)
                ->where('estado', 'publicada')
                ->orderBy('fecha_evaluacion', 'desc')
                ->get();

            return $this->successResponse($calificaciones, 'Calificaciones del estudiante obtenidas exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener calificaciones del estudiante: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener calificaciones de un curso específico
     */
    public function calificacionesCurso($cursoId)
    {
        try {
            $calificaciones = Calificacion::with(['estudiante', 'leccion', 'evaluador'])
                ->where('curso_id', $cursoId)
                ->where('estado', 'publicada')
                ->orderBy('fecha_evaluacion', 'desc')
                ->get();

            return $this->successResponse($calificaciones, 'Calificaciones del curso obtenidas exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener calificaciones del curso: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener promedio de calificaciones de un estudiante en un curso
     */
    public function promedioEstudiante($estudianteId, $cursoId)
    {
        try {
            $promedio = Calificacion::where('estudiante_id', $estudianteId)
                ->where('curso_id', $cursoId)
                ->where('estado', 'publicada')
                ->avg('calificacion');

            $totalCalificaciones = Calificacion::where('estudiante_id', $estudianteId)
                ->where('curso_id', $cursoId)
                ->where('estado', 'publicada')
                ->count();

            return $this->successResponse([
                'promedio' => round($promedio, 2),
                'total_calificaciones' => $totalCalificaciones
            ], 'Promedio del estudiante obtenido exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener promedio del estudiante: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Publicar calificaciones (cambiar estado de borrador a publicada)
     */
    public function publicarCalificaciones(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'calificacion_ids' => 'required|array',
                'calificacion_ids.*' => 'exists:calificaciones,id'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            Calificacion::whereIn('id', $request->calificacion_ids)
                ->update(['estado' => 'publicada']);

            return $this->successResponse(null, 'Calificaciones publicadas exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al publicar calificaciones: ' . $e->getMessage(), 500);
        }
    }
}
