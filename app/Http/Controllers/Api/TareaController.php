<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tarea;
use App\Models\Curso;
use Illuminate\Http\Request;
use App\Traits\ApiResponses;

class TareaController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Tarea::with(['curso', 'leccion']);

        // Filtros
        if ($request->has('curso_id')) {
            $query->where('curso_id', $request->curso_id);
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $tareas = $query->orderBy('fecha_entrega', 'asc')->get();
        
        return $this->successResponse($tareas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'fecha_asignacion' => 'required|date',
            'fecha_entrega' => 'required|date|after:fecha_asignacion',
            'tipo' => 'required|in:individual,grupal,opcional',
            'curso_id' => 'required|exists:cursos,id',
            'leccion_id' => 'nullable|exists:lecciones,id',
            'estado' => 'required|in:activa,inactiva,borrador',
            'puntos_maximos' => 'required|integer|min:1|max:100'
        ]);

        $tarea = Tarea::create($request->all());
        $tarea->load(['curso', 'leccion']);

        return $this->successResponse($tarea, 'Tarea creada exitosamente', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tarea $tarea)
    {
        $tarea->load(['curso', 'leccion', 'entregas.estudiante']);
        
        return $this->successResponse($tarea);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tarea $tarea)
    {
        $request->validate([
            'titulo' => 'string|max:255',
            'descripcion' => 'string',
            'fecha_asignacion' => 'date',
            'fecha_entrega' => 'date|after:fecha_asignacion',
            'tipo' => 'in:individual,grupal,opcional',
            'curso_id' => 'exists:cursos,id',
            'leccion_id' => 'nullable|exists:lecciones,id',
            'estado' => 'in:activa,inactiva,borrador',
            'puntos_maximos' => 'integer|min:1|max:100'
        ]);

        $tarea->update($request->all());
        $tarea->load(['curso', 'leccion']);

        return $this->successResponse($tarea, 'Tarea actualizada exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tarea $tarea)
    {
        // Verificar que no haya entregas asociadas
        if ($tarea->entregas()->count() > 0) {
            return $this->errorResponse('No se puede eliminar una tarea que tiene entregas asociadas', 422);
        }

        $tarea->delete();
        
        return $this->successResponse(null, 'Tarea eliminada exitosamente');
    }

    /**
     * Obtener tareas de un curso específico
     */
    public function cursoTareas($cursoId)
    {
        $tareas = Tarea::where('curso_id', $cursoId)
            ->with(['leccion', 'entregas'])
            ->orderBy('fecha_entrega', 'asc')
            ->get();
        
        return $this->successResponse($tareas);
    }

    /**
     * Obtener tareas de una lección específica
     */
    public function leccionTareas($leccionId)
    {
        $tareas = Tarea::where('leccion_id', $leccionId)
            ->with(['curso', 'entregas'])
            ->orderBy('fecha_entrega', 'asc')
            ->get();
        
        return $this->successResponse($tareas);
    }
} 