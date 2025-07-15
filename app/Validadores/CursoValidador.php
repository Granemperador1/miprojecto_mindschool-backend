<?php

namespace App\Validadores;

use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

/**
 * Validador específico para operaciones relacionadas con cursos
 * 
 * Responsabilidades:
 * - Validar datos de creación de cursos
 * - Validar datos de actualización de cursos
 * - Validar filtros de búsqueda
 * - Proporcionar mensajes de error en español
 */
class CursoValidador
{
    /**
     * Reglas de validación para crear un nuevo curso
     * 
     * @return array Reglas de validación
     */
    public static function reglasCreacion(): array
    {
        return [
            'titulo' => 'required|string|max:255|min:3',
            'descripcion' => 'required|string|min:10|max:2000',
            'duracion' => 'required|integer|min:1|max:1000',
            'nivel' => 'required|string|in:principiante,intermedio,avanzado',
            'precio' => 'required|numeric|min:0|max:999999.99',
            'estado' => 'required|string|in:activo,inactivo,borrador',
            'instructor_id' => 'required|exists:users,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'imagen_url' => 'nullable|url|max:500',
            'video_introduccion' => 'nullable|url|max:500',
            'requisitos_previos' => 'nullable|string|max:1000',
            'objetivos_aprendizaje' => 'nullable|string|max:1000',
            'materiales_incluidos' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Reglas de validación para actualizar un curso existente
     * 
     * @return array Reglas de validación
     */
    public static function reglasActualizacion(): array
    {
        return [
            'titulo' => 'sometimes|string|max:255|min:3',
            'descripcion' => 'sometimes|string|min:10|max:2000',
            'duracion' => 'sometimes|integer|min:1|max:1000',
            'nivel' => 'sometimes|string|in:principiante,intermedio,avanzado',
            'precio' => 'sometimes|numeric|min:0|max:999999.99',
            'estado' => 'sometimes|string|in:activo,inactivo,borrador',
            'instructor_id' => 'sometimes|exists:users,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'imagen_url' => 'nullable|url|max:500',
            'video_introduccion' => 'nullable|url|max:500',
            'requisitos_previos' => 'nullable|string|max:1000',
            'objetivos_aprendizaje' => 'nullable|string|max:1000',
            'materiales_incluidos' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Reglas de validación para filtros de búsqueda
     * 
     * @return array Reglas de validación
     */
    public static function reglasFiltros(): array
    {
        return [
            'nivel' => 'nullable|string|in:principiante,intermedio,avanzado',
            'instructor_id' => 'nullable|integer|exists:users,id',
            'precio_minimo' => 'nullable|numeric|min:0',
            'precio_maximo' => 'nullable|numeric|min:0|gte:precio_minimo',
            'duracion_minima' => 'nullable|integer|min:1',
            'duracion_maxima' => 'nullable|integer|min:1|gte:duracion_minima',
            'fecha_desde' => 'nullable|date|before_or_equal:today',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
            'orden' => 'nullable|string|in:titulo,precio,duracion,created_at,updated_at',
            'direccion' => 'nullable|string|in:asc,desc'
        ];
    }

    /**
     * Reglas de validación para búsqueda por término
     * 
     * @return array Reglas de validación
     */
    public static function reglasBusqueda(): array
    {
        return [
            'termino' => 'required|string|min:2|max:100',
            'nivel' => 'nullable|string|in:principiante,intermedio,avanzado',
            'instructor_id' => 'nullable|integer|exists:users,id',
            'precio_minimo' => 'nullable|numeric|min:0',
            'precio_maximo' => 'nullable|numeric|min:0|gte:precio_minimo'
        ];
    }

    /**
     * Mensajes de error personalizados en español
     * 
     * @return array Mensajes de error
     */
    public static function mensajesError(): array
    {
        return [
            // Mensajes para creación
            'titulo.required' => 'El título del curso es obligatorio',
            'titulo.min' => 'El título debe tener al menos 3 caracteres',
            'titulo.max' => 'El título no puede exceder 255 caracteres',
            
            'descripcion.required' => 'La descripción del curso es obligatoria',
            'descripcion.min' => 'La descripción debe tener al menos 10 caracteres',
            'descripcion.max' => 'La descripción no puede exceder 2000 caracteres',
            
            'duracion.required' => 'La duración del curso es obligatoria',
            'duracion.integer' => 'La duración debe ser un número entero',
            'duracion.min' => 'La duración debe ser al menos 1 minuto',
            'duracion.max' => 'La duración no puede exceder 1000 minutos',
            
            'nivel.required' => 'El nivel del curso es obligatorio',
            'nivel.in' => 'El nivel debe ser: principiante, intermedio o avanzado',
            
            'precio.required' => 'El precio del curso es obligatorio',
            'precio.numeric' => 'El precio debe ser un número',
            'precio.min' => 'El precio no puede ser negativo',
            'precio.max' => 'El precio no puede exceder 999,999.99',
            
            'estado.required' => 'El estado del curso es obligatorio',
            'estado.in' => 'El estado debe ser: activo, inactivo o borrador',
            
            'instructor_id.required' => 'El instructor es obligatorio',
            'instructor_id.exists' => 'El instructor seleccionado no existe',
            
            'categoria_id.exists' => 'La categoría seleccionada no existe',
            
            'imagen_url.url' => 'La URL de la imagen debe ser válida',
            'imagen_url.max' => 'La URL de la imagen no puede exceder 500 caracteres',
            
            'video_introduccion.url' => 'La URL del video debe ser válida',
            'video_introduccion.max' => 'La URL del video no puede exceder 500 caracteres',
            
            'requisitos_previos.max' => 'Los requisitos previos no pueden exceder 1000 caracteres',
            'objetivos_aprendizaje.max' => 'Los objetivos de aprendizaje no pueden exceder 1000 caracteres',
            'materiales_incluidos.max' => 'Los materiales incluidos no pueden exceder 1000 caracteres',
            
            // Mensajes para filtros
            'precio_maximo.gte' => 'El precio máximo debe ser mayor o igual al precio mínimo',
            'duracion_maxima.gte' => 'La duración máxima debe ser mayor o igual a la duración mínima',
            'fecha_desde.before_or_equal' => 'La fecha desde no puede ser posterior a hoy',
            'fecha_hasta.after_or_equal' => 'La fecha hasta debe ser posterior o igual a la fecha desde',
            'orden.in' => 'El orden debe ser: titulo, precio, duracion, created_at o updated_at',
            'direccion.in' => 'La dirección debe ser: asc o desc',
            
            // Mensajes para búsqueda
            'termino.required' => 'El término de búsqueda es obligatorio',
            'termino.min' => 'El término de búsqueda debe tener al menos 2 caracteres',
            'termino.max' => 'El término de búsqueda no puede exceder 100 caracteres'
        ];
    }

    /**
     * Valida datos para crear un curso
     * 
     * @param array $datos Datos a validar
     * @return Validator Instancia del validador
     */
    public static function validarCreacion(array $datos): Validator
    {
        return ValidatorFacade::make($datos, self::reglasCreacion(), self::mensajesError());
    }

    /**
     * Valida datos para actualizar un curso
     * 
     * @param array $datos Datos a validar
     * @return Validator Instancia del validador
     */
    public static function validarActualizacion(array $datos): Validator
    {
        return ValidatorFacade::make($datos, self::reglasActualizacion(), self::mensajesError());
    }

    /**
     * Valida filtros de búsqueda
     * 
     * @param array $filtros Filtros a validar
     * @return Validator Instancia del validador
     */
    public static function validarFiltros(array $filtros): Validator
    {
        return ValidatorFacade::make($filtros, self::reglasFiltros(), self::mensajesError());
    }

    /**
     * Valida datos de búsqueda
     * 
     * @param array $datos Datos de búsqueda a validar
     * @return Validator Instancia del validador
     */
    public static function validarBusqueda(array $datos): Validator
    {
        return ValidatorFacade::make($datos, self::reglasBusqueda(), self::mensajesError());
    }

    /**
     * Sanitiza los datos de entrada antes de la validación
     * 
     * @param array $datos Datos a sanitizar
     * @return array Datos sanitizados
     */
    public static function sanitizarDatos(array $datos): array
    {
        return [
            'titulo' => trim($datos['titulo'] ?? ''),
            'descripcion' => trim($datos['descripcion'] ?? ''),
            'duracion' => (int) ($datos['duracion'] ?? 0),
            'nivel' => strtolower(trim($datos['nivel'] ?? '')),
            'precio' => (float) ($datos['precio'] ?? 0),
            'estado' => strtolower(trim($datos['estado'] ?? '')),
            'instructor_id' => (int) ($datos['instructor_id'] ?? 0),
            'categoria_id' => isset($datos['categoria_id']) ? (int) $datos['categoria_id'] : null,
            'imagen_url' => trim($datos['imagen_url'] ?? ''),
            'video_introduccion' => trim($datos['video_introduccion'] ?? ''),
            'requisitos_previos' => trim($datos['requisitos_previos'] ?? ''),
            'objetivos_aprendizaje' => trim($datos['objetivos_aprendizaje'] ?? ''),
            'materiales_incluidos' => trim($datos['materiales_incluidos'] ?? '')
        ];
    }

    /**
     * Sanitiza filtros de búsqueda
     * 
     * @param array $filtros Filtros a sanitizar
     * @return array Filtros sanitizados
     */
    public static function sanitizarFiltros(array $filtros): array
    {
        return [
            'nivel' => isset($filtros['nivel']) ? strtolower(trim($filtros['nivel'])) : null,
            'instructor_id' => isset($filtros['instructor_id']) ? (int) $filtros['instructor_id'] : null,
            'precio_minimo' => isset($filtros['precio_minimo']) ? (float) $filtros['precio_minimo'] : null,
            'precio_maximo' => isset($filtros['precio_maximo']) ? (float) $filtros['precio_maximo'] : null,
            'duracion_minima' => isset($filtros['duracion_minima']) ? (int) $filtros['duracion_minima'] : null,
            'duracion_maxima' => isset($filtros['duracion_maxima']) ? (int) $filtros['duracion_maxima'] : null,
            'fecha_desde' => isset($filtros['fecha_desde']) ? trim($filtros['fecha_desde']) : null,
            'fecha_hasta' => isset($filtros['fecha_hasta']) ? trim($filtros['fecha_hasta']) : null,
            'orden' => isset($filtros['orden']) ? strtolower(trim($filtros['orden'])) : 'created_at',
            'direccion' => isset($filtros['direccion']) ? strtolower(trim($filtros['direccion'])) : 'desc'
        ];
    }
} 