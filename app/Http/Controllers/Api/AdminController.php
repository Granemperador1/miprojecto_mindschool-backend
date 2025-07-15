<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Leccion;
use App\Models\Tarea;
use App\Models\EntregaTarea;
use App\Models\Mensaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponses;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    use ApiResponses;

    /**
     * Obtener dashboard del administrador
     */
    public function dashboard()
    {
        try {
            // Estadísticas generales
            $stats = [
                'total_usuarios' => User::count(),
                'total_profesores' => User::role('profesor')->count(),
                'total_estudiantes' => User::role('estudiante')->count(),
                'total_cursos' => Curso::count(),
                'total_inscripciones' => Inscripcion::count(),
                'total_lecciones' => Leccion::count(),
                'total_tareas' => Tarea::count(),
                'total_entregas' => EntregaTarea::count(),
            ];

            // Actividad reciente
            $recentActivity = $this->getRecentActivity();

            // Usuarios recientes
            $recentUsers = User::with('roles')
                ->latest()
                ->take(5)
                ->get();

            // Cursos más populares
            $popularCourses = Curso::withCount('inscripciones')
                ->orderBy('inscripciones_count', 'desc')
                ->take(5)
                ->get();

            return $this->successResponse([
                'stats' => $stats,
                'recent_activity' => $recentActivity,
                'recent_users' => $recentUsers,
                'popular_courses' => $popularCourses
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener datos del dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Obtener lista de usuarios con filtros
     */
    public function getUsers(Request $request)
    {
        try {
            $query = User::with('roles');

            // Filtros
            if ($request->has('role')) {
                $query->role($request->role);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $query->paginate(15);

            return $this->successResponse($users);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener usuarios: ' . $e->getMessage());
        }
    }

    /**
     * Crear nuevo usuario (profesor o estudiante)
     */
    public function createUser(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role' => 'required|in:profesor,estudiante',
                'avatar_url' => 'nullable|string'
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'avatar_url' => $request->avatar_url
            ]);

            // Asignar rol
            $user->assignRole($request->role);

            return $this->successResponse($user->load('roles'), 'Usuario creado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear usuario: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar usuario
     */
    public function updateUser(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $id,
                'role' => 'required|in:profesor,estudiante',
                'avatar_url' => 'nullable|string'
            ]);

            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'avatar_url' => $request->avatar_url
            ]);

            // Actualizar rol
            $user->syncRoles([$request->role]);

            return $this->successResponse($user->load('roles'), 'Usuario actualizado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar usuario: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar usuario
     */
    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Verificar que no sea el último admin
            if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
                return $this->errorResponse('No se puede eliminar el último administrador');
            }

            $user->delete();

            return $this->successResponse(null, 'Usuario eliminado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar usuario: ' . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas detalladas
     */
    public function getStats()
    {
        try {
            $stats = [
                'usuarios' => [
                    'total' => User::count(),
                    'por_rol' => [
                        'admin' => User::role('admin')->count(),
                        'profesor' => User::role('profesor')->count(),
                        'estudiante' => User::role('estudiante')->count()
                    ],
                    'nuevos_este_mes' => User::whereMonth('created_at', now()->month)->count()
                ],
                'cursos' => [
                    'total' => Curso::count(),
                    'activos' => Curso::where('estado', 'activo')->count(),
                    'con_estudiantes' => Curso::has('inscripciones')->count()
                ],
                'inscripciones' => [
                    'total' => Inscripcion::count(),
                    'este_mes' => Inscripcion::whereMonth('created_at', now()->month)->count()
                ],
                'actividad' => [
                    'lecciones_creadas' => Leccion::whereMonth('created_at', now()->month)->count(),
                    'tareas_creadas' => Tarea::whereMonth('created_at', now()->month)->count(),
                    'entregas_recibidas' => EntregaTarea::whereMonth('created_at', now()->month)->count()
                ]
            ];

            return $this->successResponse($stats);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener estadísticas: ' . $e->getMessage());
        }
    }

    /**
     * Obtener actividad reciente
     */
    private function getRecentActivity()
    {
        $activities = collect();

        // Usuarios recientes
        $recentUsers = User::latest()->take(3)->get();
        foreach ($recentUsers as $user) {
            $activities->push([
                'type' => 'user_created',
                'message' => "Nuevo {$user->roles->first()->name} registrado: {$user->name}",
                'time' => $user->created_at->diffForHumans(),
                'data' => $user
            ]);
        }

        // Cursos recientes
        $recentCourses = Curso::with('instructor')->latest()->take(3)->get();
        foreach ($recentCourses as $course) {
            $activities->push([
                'type' => 'course_created',
                'message' => "Nuevo curso creado: {$course->titulo}",
                'time' => $course->created_at->diffForHumans(),
                'data' => $course
            ]);
        }

        // Inscripciones recientes
        $recentEnrollments = Inscripcion::with(['alumno', 'curso'])->latest()->take(3)->get();
        foreach ($recentEnrollments as $enrollment) {
            $activities->push([
                'type' => 'enrollment_created',
                'message' => "{$enrollment->alumno->name} se inscribió en {$enrollment->curso->titulo}",
                'time' => $enrollment->created_at->diffForHumans(),
                'data' => $enrollment
            ]);
        }

        return $activities->sortByDesc('time')->take(10);
    }
} 