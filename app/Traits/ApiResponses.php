<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

trait ApiResponses
{
    /**
     * Respuesta exitosa con datos
     */
    protected function successResponse($data = null, string $message = 'Operación exitosa', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Respuesta de error
     */
    protected function errorResponse(string $message = 'Error en la operación', int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Respuesta de recurso no encontrado
     */
    protected function notFoundResponse(string $message = 'Recurso no encontrado'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Respuesta de validación fallida
     */
    protected function validationErrorResponse($errors, string $message = 'Los datos proporcionados no son válidos'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Respuesta de acceso denegado
     */
    protected function forbiddenResponse(string $message = 'Acceso denegado'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Respuesta de recurso creado
     */
    protected function createdResponse($data = null, string $message = 'Recurso creado exitosamente'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Respuesta de recurso actualizado
     */
    protected function updatedResponse($data = null, string $message = 'Recurso actualizado exitosamente'): JsonResponse
    {
        return $this->successResponse($data, $message, 200);
    }

    /**
     * Respuesta de recurso eliminado
     */
    protected function deletedResponse(string $message = 'Recurso eliminado exitosamente'): JsonResponse
    {
        return $this->successResponse(null, $message, 200);
    }
} 