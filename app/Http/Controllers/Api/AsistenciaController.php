<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponses;

class AsistenciaController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Asistencia::with(['estudiante', 'curso', 'registrador']);

            // Filtros
            if ($request->has('estudiante_id')) {
                $query->where('estudiante_id', $request->estudiante_id);
            }

            if ($request->has('curso_id')) {
                $query->where('curso_id', $request->curso_id);
            }

            if ($request->has('fecha')) {
                $query->whereDate('fecha', $request->fecha);
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            // Si es profesor, solo mostrar asistencias de sus cursos
            if (auth()->user()->hasRole('profesor')) {
                $query->whereHas('curso', function($q) {
                    $q->where('instructor_id', auth()->id());
                });
            }

            // Si es estudiante, solo mostrar sus propias asistencias
            if (auth()->user()->hasRole('estudiante')) {
                $query->where('estudiante_id', auth()->id());
            }

            $asistencias = $query->orderBy('fecha', 'desc')->paginate(15);

            return $this->successResponse($asistencias, 'Asistencias obtenidas exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener asistencias: ' . $e->getMessage(), 500);
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
                'fecha' => 'required|date',
                'estado' => 'required|in:presente,ausente,tardanza,justificado',
                'observaciones' => 'nullable|string|max:500',
                'registrador_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            // Verificar que el usuario tenga permisos para registrar asistencias
            if (auth()->user()->hasRole('profesor')) {
                $curso = Curso::find($request->curso_id);
                if (!$curso || $curso->instructor_id !== auth()->id()) {
                    return $this->errorResponse('No tienes permisos para registrar asistencias en este curso', 403);
                }
            }

            // Verificar que no exista ya un registro para este estudiante, curso y fecha
            $asistenciaExistente = Asistencia::where('estudiante_id', $request->estudiante_id)
                ->where('curso_id', $request->curso_id)
                ->whereDate('fecha', $request->fecha)
                ->first();

            if ($asistenciaExistente) {
                return $this->errorResponse('Ya existe un registro de asistencia para este estudiante en esta fecha', 422);
            }

            $asistencia = Asistencia::create($request->all());
            $asistencia->load(['estudiante', 'curso', 'registrador']);

            return $this->successResponse($asistencia, 'Asistencia registrada exitosamente', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al registrar asistencia: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Asistencia $asistencia)
    {
        try {
            // Verificar permisos
            if (auth()->user()->hasRole('profesor')) {
                if ($asistencia->curso->instructor_id !== auth()->id()) {
                    return $this->errorResponse('No tienes permisos para ver esta asistencia', 403);
                }
            }

            if (auth()->user()->hasRole('estudiante')) {
                if ($asistencia->estudiante_id !== auth()->id()) {
                    return $this->errorResponse('No tienes permisos para ver esta asistencia', 403);
                }
            }

            $asistencia->load(['estudiante', 'curso', 'registrador']);
            return $this->successResponse($asistencia, 'Asistencia obtenida exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener asistencia: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Asistencia $asistencia)
    {
        try {
            $validator = Validator::make($request->all(), [
                'estado' => 'in:presente,ausente,tardanza,justificado',
                'observaciones' => 'string|max:500'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            // Verificar permisos
            if (auth()->user()->hasRole('profesor')) {
                if ($asistencia->curso->instructor_id !== auth()->id()) {
                    return $this->errorResponse('No tienes permisos para editar esta asistencia', 403);
                }
            }

            $asistencia->update($request->all());
            $asistencia->refresh();
            $asistencia->load(['estudiante', 'curso', 'registrador']);

            return $this->successResponse($asistencia, 'Asistencia actualizada exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar asistencia: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Asistencia $asistencia)
    {
        try {
            // Verificar permisos
            if (auth()->user()->hasRole('profesor')) {
                if ($asistencia->curso->instructor_id !== auth()->id()) {
                    return $this->errorResponse('No tienes permisos para eliminar esta asistencia', 403);
                }
            }

            $asistencia->delete();
            return $this->successResponse(null, 'Asistencia eliminada exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar asistencia: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener asistencias de un curso específico
     */
    public function cursoAsistencias($cursoId)
    {
        try {
            $query = Asistencia::where('curso_id', $cursoId)
                ->with(['estudiante', 'registrador'])
                ->orderBy('fecha', 'desc');

            // Si es profesor, verificar que sea su curso
            if (auth()->user()->hasRole('profesor')) {
                $curso = Curso::find($cursoId);
                if (!$curso || $curso->instructor_id !== auth()->id()) {
                    return $this->errorResponse('No tienes permisos para ver asistencias de este curso', 403);
                }
            }

            $asistencias = $query->get();
            return $this->successResponse($asistencias, 'Asistencias del curso obtenidas exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener asistencias del curso: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener asistencias de un estudiante específico
     */
    public function estudianteAsistencias($estudianteId)
    {
        try {
            // Verificar permisos
            if (auth()->user()->hasRole('estudiante')) {
                if ($estudianteId != auth()->id()) {
                    return $this->errorResponse('No tienes permisos para ver asistencias de otros estudiantes', 403);
                }
            }

            $asistencias = Asistencia::where('estudiante_id', $estudianteId)
                ->with(['curso', 'registrador'])
                ->orderBy('fecha', 'desc')
                ->get();

            return $this->successResponse($asistencias, 'Asistencias del estudiante obtenidas exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener asistencias del estudiante: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener estadísticas de asistencia de un curso
     */
    public function estadisticasCurso($cursoId)
    {
        try {
            // Verificar permisos
            if (auth()->user()->hasRole('profesor')) {
                $curso = Curso::find($cursoId);
                if (!$curso || $curso->instructor_id !== auth()->id()) {
                    return $this->errorResponse('No tienes permisos para ver estadísticas de este curso', 403);
                }
            }

            $totalAsistencias = Asistencia::where('curso_id', $cursoId)->count();
            $presentes = Asistencia::where('curso_id', $cursoId)->where('estado', 'presente')->count();
            $ausentes = Asistencia::where('curso_id', $cursoId)->where('estado', 'ausente')->count();
            $tardanzas = Asistencia::where('curso_id', $cursoId)->where('estado', 'tardanza')->count();
            $justificados = Asistencia::where('curso_id', $cursoId)->where('estado', 'justificado')->count();

            $porcentajeAsistencia = $totalAsistencias > 0 ? round(($presentes / $totalAsistencias) * 100, 2) : 0;

            return $this->successResponse([
                'total_asistencias' => $totalAsistencias,
                'presentes' => $presentes,
                'ausentes' => $ausentes,
                'tardanzas' => $tardanzas,
                'justificados' => $justificados,
                'porcentaje_asistencia' => $porcentajeAsistencia
            ], 'Estadísticas de asistencia obtenidas exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener estadísticas de asistencia: ' . $e->getMessage(), 500);
        }
    }
} 