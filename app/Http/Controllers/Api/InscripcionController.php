<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Curso;
use App\Notifications\NuevaInscripcion;

class InscripcionController extends Controller
{
    public function index()
    {
        $inscripciones = Inscripcion::with(['alumno', 'curso'])->get();
        return response()->json(['data' => $inscripciones]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'curso_id' => 'required|exists:cursos,id',
            'estado' => 'required|in:activo,completado,cancelado,en_progreso',
            'fecha_inscripcion' => 'date',
            'progreso' => 'integer|min:0|max:100'
        ]);

        // Verificar que no esté ya inscrito en el curso
        $inscripcionExistente = Inscripcion::where('user_id', $request->user_id)
            ->where('curso_id', $request->curso_id)
            ->first();

        if ($inscripcionExistente) {
            return response()->json([
                'message' => 'El usuario ya está inscrito en este curso',
                'errors' => [
                    'curso_id' => ['El usuario ya está inscrito en este curso']
                ]
            ], 422);
        }

        $inscripcion = Inscripcion::create($request->all());

        $curso = Curso::find($request->curso_id);
        $alumno = User::find($request->user_id);

        $instructor = User::find($curso->instructor_id);

        if ($instructor) {
            $instructor->notify(new NuevaInscripcion([
                'curso' => $curso->titulo,
                'alumno' => $alumno->name,
            ]));
        }

        return response()->json(['data' => $inscripcion], 201);
    }

    public function show(Request $request, $id)
    {
        $inscripcion = Inscripcion::find($id);
        if (!$inscripcion) {
            return response()->json(['message' => 'Inscripción no encontrada'], 404);
        }
        $userId = $request->user()->id;
        if ($userId !== $inscripcion->user_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        $inscripcion->load(['alumno', 'curso']);
        return response()->json(['data' => $inscripcion]);
    }

    public function update(Request $request, $id)
    {
        $inscripcion = Inscripcion::find($id);
        if (!$inscripcion) {
            return response()->json(['message' => 'Inscripción no encontrada'], 404);
        }
        if ($request->user()->id !== $inscripcion->user_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        $request->validate([
            'user_id' => 'exists:users,id',
            'curso_id' => 'exists:cursos,id',
            'estado' => 'in:activo,completado,cancelado,en_progreso',
            'progreso' => 'integer|min:0|max:100'
        ]);
        $inscripcion->update($request->all());
        $inscripcion->refresh();
        return response()->json(['data' => $inscripcion]);
    }

    public function destroy(Request $request, $id)
    {
        $inscripcion = Inscripcion::find($id);
        if (!$inscripcion) {
            return response()->json(['message' => 'Inscripción no encontrada'], 404);
        }
        if ($request->user()->id !== $inscripcion->user_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        $inscripcion->delete();
        return response()->json(['message' => 'Inscripción cancelada exitosamente']);
    }

    public function cursoInscripciones(Curso $curso)
    {
        $inscripciones = Inscripcion::with(['alumno', 'curso'])
            ->where('curso_id', $curso->id)
            ->get();
        
        return response()->json(['data' => $inscripciones]);
    }
} 