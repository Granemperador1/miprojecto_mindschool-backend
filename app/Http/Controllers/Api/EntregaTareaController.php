<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EntregaTarea;
use App\Models\Tarea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Traits\ApiResponses;

class EntregaTareaController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = EntregaTarea::with(['tarea.curso', 'estudiante']);

        // Filtros
        if ($request->has('tarea_id')) {
            $query->where('tarea_id', $request->tarea_id);
        }

        if ($request->has('estudiante_id')) {
            $query->where('estudiante_id', $request->estudiante_id);
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $entregas = $query->orderBy('fecha_entrega', 'desc')->get();
        
        return $this->successResponse($entregas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tarea_id' => 'required|exists:tareas,id',
            'estudiante_id' => 'required|exists:users,id',
            'archivo' => 'required|file|max:1048576', // 1GB max
            'comentarios' => 'nullable|string',
            'estado' => 'required|in:pendiente,revisada,calificada'
        ]);

        // Verificar que no haya una entrega previa para esta tarea y estudiante
        $entregaExistente = EntregaTarea::where('tarea_id', $request->tarea_id)
            ->where('estudiante_id', $request->estudiante_id)
            ->first();

        if ($entregaExistente) {
            return $this->errorResponse('Ya existe una entrega para esta tarea', 422);
        }

        // Subir archivo
        $archivoPath = $request->file('archivo')->store('entregas', 'public');

        $entrega = EntregaTarea::create([
            'tarea_id' => $request->tarea_id,
            'estudiante_id' => $request->estudiante_id,
            'archivo_url' => $archivoPath,
            'comentarios' => $request->comentarios,
            'estado' => $request->estado,
            'fecha_entrega' => now()
        ]);

        $entrega->load(['tarea.curso', 'estudiante']);

        return $this->successResponse($entrega, 'Entrega realizada exitosamente', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(EntregaTarea $entrega)
    {
        $entrega->load(['tarea.curso', 'estudiante']);
        
        return $this->successResponse($entrega);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EntregaTarea $entrega)
    {
        $request->validate([
            'comentarios' => 'string',
            'calificacion' => 'nullable|numeric|min:0|max:100',
            'comentarios_profesor' => 'string',
            'estado' => 'in:pendiente,revisada,calificada'
        ]);

        $entrega->update($request->all());
        $entrega->load(['tarea.curso', 'estudiante']);

        return $this->successResponse($entrega, 'Entrega actualizada exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EntregaTarea $entrega)
    {
        // Eliminar archivo físico
        if ($entrega->archivo_url && Storage::disk('public')->exists($entrega->archivo_url)) {
            Storage::disk('public')->delete($entrega->archivo_url);
        }

        $entrega->delete();
        
        return $this->successResponse(null, 'Entrega eliminada exitosamente');
    }

    /**
     * Calificar una entrega
     */
    public function calificar(Request $request, EntregaTarea $entrega)
    {
        $request->validate([
            'calificacion' => 'required|numeric|min:0|max:100',
            'comentarios_profesor' => 'nullable|string'
        ]);

        $entrega->update([
            'calificacion' => $request->calificacion,
            'comentarios_profesor' => $request->comentarios_profesor,
            'estado' => 'calificada'
        ]);

        $entrega->load(['tarea.curso', 'estudiante']);

        return $this->successResponse($entrega, 'Entrega calificada exitosamente');
    }

    /**
     * Obtener entregas de una tarea específica
     */
    public function tareaEntregas($tareaId)
    {
        $entregas = EntregaTarea::where('tarea_id', $tareaId)
            ->with(['estudiante'])
            ->orderBy('fecha_entrega', 'desc')
            ->get();
        
        return $this->successResponse($entregas);
    }

    /**
     * Obtener entregas de un estudiante específico
     */
    public function estudianteEntregas($estudianteId)
    {
        $entregas = EntregaTarea::where('estudiante_id', $estudianteId)
            ->with(['tarea.curso'])
            ->orderBy('fecha_entrega', 'desc')
            ->get();
        
        return $this->successResponse($entregas);
    }
} 