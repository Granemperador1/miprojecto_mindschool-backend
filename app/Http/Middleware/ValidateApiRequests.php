<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $rules = null): Response
    {
        if ($rules) {
            $validator = Validator::make($request->all(), $this->getRules($rules));
            
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Los datos proporcionados no son válidos.',
                    'errors' => $validator->errors()
                ], 422);
            }
        }

        return $next($request);
    }

    private function getRules(string $ruleSet): array
    {
        $rules = [
            'curso' => [
                'titulo' => 'required|string|max:255',
                'descripcion' => 'required|string',
                'duracion' => 'required|integer|min:1',
                'nivel' => 'required|in:principiante,intermedio,avanzado',
                'precio' => 'required|numeric|min:0',
                'estado' => 'required|in:activo,inactivo,borrador',
                'instructor_id' => 'required|exists:users,id'
            ],
            'leccion' => [
                'titulo' => 'required|string|max:255',
                'descripcion' => 'required|string',
                'contenido' => 'required|string',
                'duracion' => 'required|integer|min:1',
                'orden' => 'required|integer|min:1',
                'curso_id' => 'required|exists:cursos,id',
                'estado' => 'required|in:activo,inactivo,borrador'
            ],
            'inscripcion' => [
                'user_id' => 'required|exists:users,id',
                'curso_id' => 'required|exists:cursos,id',
                'estado' => 'required|in:activo,completado,cancelado,en_progreso',
                'progreso' => 'integer|min:0|max:100'
            ],
            'usuario' => [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role' => 'required|string|in:estudiante,profesor,admin'
            ]
        ];

        return $rules[$ruleSet] ?? [];
    }
} 