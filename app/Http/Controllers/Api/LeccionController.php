<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leccion;
use Illuminate\Http\Request;

class LeccionController extends Controller
{
    public function index()
    {
        $lecciones = Leccion::all();
        return response()->json(['data' => $lecciones]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'contenido' => 'required|string',
            'duracion' => 'required|integer|min:1',
            'orden' => 'required|integer|min:1',
            'curso_id' => 'required|exists:cursos,id',
            'estado' => 'required|in:activo,inactivo,borrador'
        ]);

        $leccion = Leccion::create($request->all());
        return response()->json(['data' => $leccion], 201);
    }

    public function show($id)
    {
        $leccion = Leccion::find($id);
        if (!$leccion) {
            return response()->json(['message' => 'Lección no encontrada'], 404);
        }
        return response()->json(['data' => $leccion]);
    }

    public function update(Request $request, $id)
    {
        $leccion = Leccion::find($id);
        if (!$leccion) {
            return response()->json(['message' => 'Lección no encontrada'], 404);
        }
        $request->validate([
            'titulo' => 'string|max:255',
            'descripcion' => 'string',
            'contenido' => 'string',
            'duracion' => 'integer|min:1',
            'orden' => 'integer|min:1',
            'curso_id' => 'exists:cursos,id',
            'estado' => 'in:activo,inactivo,borrador'
        ]);
        $leccion->update($request->all());
        $leccion->refresh();
        return response()->json(['data' => $leccion]);
    }

    public function destroy(Request $request, $id)
    {
        $leccion = Leccion::find($id);
        if (!$leccion) {
            return response()->json(['message' => 'Lección no encontrada'], 404);
        }
        $leccion->delete();
        return response()->json(['message' => 'Lección eliminada exitosamente']);
    }

    public function cursoLecciones($cursoId)
    {
        $lecciones = Leccion::where('curso_id', $cursoId)
            ->orderBy('orden')
            ->get();
        
        return response()->json(['data' => $lecciones]);
    }
} 