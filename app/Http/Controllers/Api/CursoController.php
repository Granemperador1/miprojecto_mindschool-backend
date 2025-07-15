<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Repositorios\CursoRepositorio;
use App\Validadores\CursoValidador;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para manejar todas las operaciones relacionadas con cursos
 * 
 * Responsabilidades:
 * - Gestionar peticiones HTTP para cursos
 * - Validar datos de entrada
 * - Coordinar con repositorios y servicios
 * - Devolver respuestas JSON estandarizadas
 */
class CursoController extends Controller
{
    use ApiResponses;

    private CursoRepositorio $cursoRepositorio;

    /**
     * Constructor del controlador
     * 
     * @param CursoRepositorio $cursoRepositorio Repositorio de cursos
     */
    public function __construct(CursoRepositorio $cursoRepositorio)
    {
        $this->cursoRepositorio = $cursoRepositorio;
    }

    /**
     * Obtiene listado paginado de cursos con filtros opcionales
     * 
     * @param Request $request Petición HTTP
     * @return JsonResponse Respuesta JSON con listado de cursos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Sanitizar y validar filtros
            $filtros = CursoValidador::sanitizarFiltros($request->all());
            $validador = CursoValidador::validarFiltros($filtros);

            if ($validador->fails()) {
                return $this->errorResponse(
                    'Los filtros proporcionados no son válidos',
                    422,
                    $validador->errors()
                );
            }

            // Obtener parámetros de ordenamiento
            $orden = $filtros['orden'] ?? 'created_at';
            $direccion = $filtros['direccion'] ?? 'desc';

            // Obtener cursos del repositorio
            $cursos = $this->cursoRepositorio->obtenerListadoConFiltros($filtros, $orden, $direccion);

            Log::channel('api')->info('Listado de cursos consultado', [
                'usuario_id' => $request->user()->id ?? null,
                'filtros_aplicados' => $filtros,
                'total_resultados' => $cursos->total()
            ]);

            return $this->successResponse(
                $cursos,
                'Listado de cursos obtenido exitosamente'
            );

        } catch (\Exception $excepcion) {
            Log::channel('critical')->error('Error al obtener listado de cursos', [
                'error' => $excepcion->getMessage(),
                'usuario_id' => $request->user()->id ?? null
            ]);

            return $this->errorResponse(
                'Error interno del servidor al obtener cursos',
                500
            );
        }
    }

    /**
     * Obtiene un curso específico con todas sus relaciones
     * 
     * @param int $idCurso ID del curso
     * @return JsonResponse Respuesta JSON con datos del curso
     */
    public function show(int $idCurso): JsonResponse
    {
        try {
            $curso = $this->cursoRepositorio->obtenerConRelaciones($idCurso);

            if (!$curso) {
                return $this->errorResponse(
                    'El curso solicitado no existe',
                    404
                );
            }

            Log::channel('api')->info('Curso consultado', [
                'curso_id' => $idCurso,
                'titulo' => $curso->titulo
            ]);

            return $this->successResponse(
                $curso,
                'Curso obtenido exitosamente'
            );

        } catch (\Exception $excepcion) {
            Log::channel('critical')->error('Error al obtener curso', [
                'error' => $excepcion->getMessage(),
                'curso_id' => $idCurso
            ]);

            return $this->errorResponse(
                'Error interno del servidor al obtener el curso',
                500
            );
        }
    }

    /**
     * Crea un nuevo curso
     * 
     * @param Request $request Petición HTTP con datos del curso
     * @return JsonResponse Respuesta JSON con el curso creado
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Sanitizar y validar datos
            $datos = CursoValidador::sanitizarDatos($request->all());
            $validador = CursoValidador::validarCreacion($datos);

            if ($validador->fails()) {
                return $this->errorResponse(
                    'Los datos proporcionados no son válidos',
                    422,
                    $validador->errors()
                );
            }

            // Verificar que el usuario sea instructor o admin
            $usuario = $request->user();
            if (!$usuario->hasRole(['profesor', 'admin'])) {
                return $this->errorResponse(
                    'No tienes permisos para crear cursos',
                    403
                );
            }

            // Asignar instructor si no se especifica
            if (!isset($datos['instructor_id'])) {
                $datos['instructor_id'] = $usuario->id;
            }

            // Crear curso
            $curso = $this->cursoRepositorio->crear($datos);

            Log::channel('audit')->info('Curso creado', [
                'curso_id' => $curso->id,
                'titulo' => $curso->titulo,
                'usuario_id' => $usuario->id,
                'instructor_id' => $curso->instructor_id
            ]);

            return $this->successResponse(
                $curso,
                'Curso creado exitosamente',
                201
            );

        } catch (\Exception $excepcion) {
            Log::channel('critical')->error('Error al crear curso', [
                'error' => $excepcion->getMessage(),
                'usuario_id' => $request->user()->id ?? null,
                'datos' => $request->all()
            ]);

            return $this->errorResponse(
                'Error interno del servidor al crear el curso',
                500
            );
        }
    }

    /**
     * Actualiza un curso existente
     * 
     * @param Request $request Petición HTTP con datos a actualizar
     * @param int $idCurso ID del curso
     * @return JsonResponse Respuesta JSON con el curso actualizado
     */
    public function update(Request $request, int $idCurso): JsonResponse
    {
        try {
            // Verificar que el curso existe
            $curso = Curso::find($idCurso);
            if (!$curso) {
                return $this->errorResponse(
                    'El curso a actualizar no existe',
                    404
                );
            }

            // Verificar permisos
            $usuario = $request->user();
            if (!$usuario->hasRole('admin') && $curso->instructor_id !== $usuario->id) {
                return $this->errorResponse(
                    'No tienes permisos para actualizar este curso',
                    403
                );
            }

            // Sanitizar y validar datos
            $datos = CursoValidador::sanitizarDatos($request->all());
            $validador = CursoValidador::validarActualizacion($datos);

            if ($validador->fails()) {
                return $this->errorResponse(
                    'Los datos proporcionados no son válidos',
                    422,
                    $validador->errors()
                );
            }

            // Actualizar curso
            $actualizado = $this->cursoRepositorio->actualizar($idCurso, $datos);

            if (!$actualizado) {
                return $this->errorResponse(
                    'No se pudo actualizar el curso',
                    500
                );
            }

            // Obtener curso actualizado
            $cursoActualizado = $this->cursoRepositorio->obtenerConRelaciones($idCurso);

            Log::channel('audit')->info('Curso actualizado', [
                'curso_id' => $idCurso,
                'usuario_id' => $usuario->id,
                'campos_actualizados' => array_keys($datos)
            ]);

            return $this->successResponse(
                $cursoActualizado,
                'Curso actualizado exitosamente'
            );

        } catch (\Exception $excepcion) {
            Log::channel('critical')->error('Error al actualizar curso', [
                'error' => $excepcion->getMessage(),
                'curso_id' => $idCurso,
                'usuario_id' => $request->user()->id ?? null
            ]);

            return $this->errorResponse(
                'Error interno del servidor al actualizar el curso',
                500
            );
        }
    }

    /**
     * Elimina un curso
     * 
     * @param int $idCurso ID del curso
     * @return JsonResponse Respuesta JSON de confirmación
     */
    public function destroy(int $idCurso): JsonResponse
    {
        try {
            // Verificar que el curso existe
            $curso = Curso::find($idCurso);
            if (!$curso) {
                return $this->errorResponse(
                    'El curso a eliminar no existe',
                    404
                );
            }

            // Verificar permisos
            $usuario = request()->user();
            if (!$usuario->hasRole('admin') && $curso->instructor_id !== $usuario->id) {
                return $this->errorResponse(
                    'No tienes permisos para eliminar este curso',
                    403
                );
            }

            // Eliminar curso
            $eliminado = $this->cursoRepositorio->eliminar($idCurso);

            if (!$eliminado) {
                return $this->errorResponse(
                    'No se pudo eliminar el curso',
                    500
                );
            }

            Log::channel('audit')->info('Curso eliminado', [
                'curso_id' => $idCurso,
                'titulo' => $curso->titulo,
                'usuario_id' => $usuario->id
            ]);

            return $this->successResponse(
                null,
                'Curso eliminado exitosamente'
            );

        } catch (\Exception $excepcion) {
            Log::channel('critical')->error('Error al eliminar curso', [
                'error' => $excepcion->getMessage(),
                'curso_id' => $idCurso,
                'usuario_id' => request()->user()->id ?? null
            ]);

            return $this->errorResponse(
                'Error interno del servidor al eliminar el curso',
                500
            );
        }
    }

    /**
     * Busca cursos por término de búsqueda
     * 
     * @param Request $request Petición HTTP con término de búsqueda
     * @return JsonResponse Respuesta JSON con resultados de búsqueda
     */
    public function buscar(Request $request): JsonResponse
    {
        try {
            // Validar datos de búsqueda
            $validador = CursoValidador::validarBusqueda($request->all());

            if ($validador->fails()) {
                return $this->errorResponse(
                    'Los datos de búsqueda no son válidos',
                    422,
                    $validador->errors()
                );
            }

            $termino = $request->input('termino');
            $filtros = CursoValidador::sanitizarFiltros($request->all());

            // Realizar búsqueda
            $resultados = $this->cursoRepositorio->buscarPorTermino($termino, $filtros);

            Log::channel('api')->info('Búsqueda de cursos realizada', [
                'termino' => $termino,
                'filtros' => $filtros,
                'total_resultados' => $resultados->total()
            ]);

            return $this->successResponse(
                $resultados,
                'Búsqueda completada exitosamente'
            );

        } catch (\Exception $excepcion) {
            Log::channel('critical')->error('Error en búsqueda de cursos', [
                'error' => $excepcion->getMessage(),
                'termino' => $request->input('termino')
            ]);

            return $this->errorResponse(
                'Error interno del servidor al realizar la búsqueda',
                500
            );
        }
    }

    /**
     * Obtiene cursos populares
     * 
     * @param Request $request Petición HTTP
     * @return JsonResponse Respuesta JSON con cursos populares
     */
    public function populares(Request $request): JsonResponse
    {
        try {
            $limite = (int) $request->input('limite', 10);
            $cursos = $this->cursoRepositorio->obtenerCursosPopulares($limite);

            Log::channel('api')->info('Cursos populares consultados', [
                'limite' => $limite,
                'total_obtenidos' => $cursos->count()
            ]);

            return $this->successResponse(
                $cursos,
                'Cursos populares obtenidos exitosamente'
            );

        } catch (\Exception $excepcion) {
            Log::channel('critical')->error('Error al obtener cursos populares', [
                'error' => $excepcion->getMessage()
            ]);

            return $this->errorResponse(
                'Error interno del servidor al obtener cursos populares',
                500
            );
        }
    }

    /**
     * Obtiene estadísticas de cursos
     * 
     * @return JsonResponse Respuesta JSON con estadísticas
     */
    public function estadisticas(): JsonResponse
    {
        try {
            $estadisticas = $this->cursoRepositorio->obtenerEstadisticas();

            Log::channel('api')->info('Estadísticas de cursos consultadas');

            return $this->successResponse(
                $estadisticas,
                'Estadísticas obtenidas exitosamente'
            );

        } catch (\Exception $excepcion) {
            Log::channel('critical')->error('Error al obtener estadísticas de cursos', [
                'error' => $excepcion->getMessage()
            ]);

            return $this->errorResponse(
                'Error interno del servidor al obtener estadísticas',
                500
            );
        }
    }

    /**
     * Invitar alumno por correo
     */
    public function invitarAlumnoPorCorreo(Request $request, $idCurso)
    {
        $request->validate(['email' => 'required|email']);
        $usuario = \App\Models\User::where('email', $request->email)->first();
        if (!$usuario) {
            return $this->errorResponse('Usuario no encontrado', 404);
        }
        // Validar que sea estudiante
        if (!$usuario->hasRole('estudiante')) {
            return $this->errorResponse('Solo se pueden invitar estudiantes', 400);
        }
        $curso = Curso::findOrFail($idCurso);
        // Validar si ya está inscrito
        if ($curso->alumnos()->where('users.id', $usuario->id)->exists()) {
            return $this->errorResponse('El alumno ya está inscrito en el curso', 409);
        }
        $curso->alumnos()->attach($usuario->id, ['tipo_acceso' => 'invitacion']);
        // Enviar notificación/correo
        $usuario->notify(new \App\Notifications\NuevaInscripcion([
            'curso' => $curso->titulo,
            'alumno' => $usuario->name
        ]));
        return $this->successResponse(null, 'Alumno invitado correctamente y notificado');
    }

    /**
     * Generar código de invitación para el curso
     */
    public function generarCodigoInvitacion($idCurso)
    {
        $curso = Curso::findOrFail($idCurso);
        // Generar código aleatorio
        $codigo = strtoupper(uniqid('INV'));
        $curso->codigo_invitacion = $codigo;
        $curso->save();
        return $this->successResponse(['codigo_invitacion' => $codigo], 'Código de invitación generado');
    }

    /**
     * Agregar alumno manualmente por correo
     */
    public function agregarAlumnoManual(Request $request, $idCurso)
    {
        $request->validate(['email' => 'required|email']);
        $usuario = \App\Models\User::where('email', $request->email)->first();
        if (!$usuario) {
            return $this->errorResponse('Usuario no encontrado', 404);
        }
        // Validar que sea estudiante
        if (!$usuario->hasRole('estudiante')) {
            return $this->errorResponse('Solo se pueden agregar estudiantes', 400);
        }
        $curso = Curso::findOrFail($idCurso);
        // Validar si ya está inscrito
        if ($curso->alumnos()->where('users.id', $usuario->id)->exists()) {
            return $this->errorResponse('El alumno ya está inscrito en el curso', 409);
        }
        $curso->alumnos()->attach($usuario->id, ['tipo_acceso' => 'manual']);
        return $this->successResponse(null, 'Alumno agregado manualmente');
    }

    /**
     * Inscribir alumno usando código de invitación
     */
    public function inscribirConCodigo(Request $request, $idCurso)
    {
        $request->validate(['codigo_invitacion' => 'required|string']);
        $curso = Curso::findOrFail($idCurso);
        if ($curso->codigo_invitacion !== $request->codigo_invitacion) {
            return $this->errorResponse('Código de invitación incorrecto', 400);
        }
        $usuario = $request->user();
        // Validar que sea estudiante
        if (!$usuario->hasRole('estudiante')) {
            return $this->errorResponse('Solo los estudiantes pueden inscribirse con código', 400);
        }
        // Validar si ya está inscrito
        if ($curso->alumnos()->where('users.id', $usuario->id)->exists()) {
            return $this->errorResponse('Ya estás inscrito en el curso', 409);
        }
        $curso->alumnos()->attach($usuario->id, ['tipo_acceso' => 'invitacion']);
        return $this->successResponse(null, 'Inscripción exitosa con código de invitación');
    }
} 