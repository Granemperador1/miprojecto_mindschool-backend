<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;

class ApiResponsesTest extends TestCase
{
    use ApiResponses;

    public function test_success_response()
    {
        $data = ['test' => 'data'];
        $response = $this->successResponse($data, 'Test message', 200);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals('Test message', $content['message']);
        $this->assertEquals($data, $content['data']);
    }

    public function test_error_response()
    {
        $response = $this->errorResponse('Test error', 400);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('Test error', $content['message']);
        $this->assertArrayNotHasKey('errors', $content);
    }

    public function test_error_response_with_errors()
    {
        $errors = ['field' => ['Error message']];
        $response = $this->errorResponse('Validation failed', 422, $errors);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('Validation failed', $content['message']);
        $this->assertEquals($errors, $content['errors']);
    }

    public function test_not_found_response()
    {
        $response = $this->notFoundResponse('Resource not found');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('Resource not found', $content['message']);
    }

    public function test_validation_error_response()
    {
        $errors = ['email' => ['Email is required']];
        $response = $this->validationErrorResponse($errors, 'Validation failed');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('Validation failed', $content['message']);
        $this->assertEquals($errors, $content['errors']);
    }

    public function test_forbidden_response()
    {
        $response = $this->forbiddenResponse('Access denied');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('Access denied', $content['message']);
    }

    public function test_created_response()
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $response = $this->createdResponse($data, 'Resource created');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals('Resource created', $content['message']);
        $this->assertEquals($data, $content['data']);
    }

    public function test_updated_response()
    {
        $data = ['id' => 1, 'name' => 'Updated'];
        $response = $this->updatedResponse($data, 'Resource updated');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals('Resource updated', $content['message']);
        $this->assertEquals($data, $content['data']);
    }

    public function test_deleted_response()
    {
        $response = $this->deletedResponse('Resource deleted');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals('Resource deleted', $content['message']);
        $this->assertNull($content['data']);
    }

    public function test_default_messages()
    {
        // Test default success message
        $response = $this->successResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Operación exitosa', $content['message']);

        // Test default error message
        $response = $this->errorResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Error en la operación', $content['message']);

        // Test default not found message
        $response = $this->notFoundResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Recurso no encontrado', $content['message']);

        // Test default forbidden message
        $response = $this->forbiddenResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Acceso denegado', $content['message']);

        // Test default created message
        $response = $this->createdResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Recurso creado exitosamente', $content['message']);

        // Test default updated message
        $response = $this->updatedResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Recurso actualizado exitosamente', $content['message']);

        // Test default deleted message
        $response = $this->deletedResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Recurso eliminado exitosamente', $content['message']);
    }
} 