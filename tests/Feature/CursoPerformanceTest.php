<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Curso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Cache;

class CursoPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuario de prueba
        $this->user = User::factory()->create();
        $this->user->assignRole('estudiante');
        
        // Crear cursos de prueba
        Curso::factory()->count(50)->create();
    }

    /** @test */
    public function listado_cursos_es_rapido()
    {
        Sanctum::actingAs($this->user);

        $startTime = microtime(true);
        
        $response = $this->getJson('/api/cursos');
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // en milisegundos

        $response->assertStatus(200);
        
        // Verificar que la respuesta es rápida (< 500ms)
        $this->assertLessThan(500, $executionTime, 
            "La consulta tardó {$executionTime}ms, debe ser menor a 500ms");
    }

    /** @test */
    public function cache_funciona_correctamente()
    {
        Sanctum::actingAs($this->user);

        // Primera consulta (sin cache)
        $startTime = microtime(true);
        $response1 = $this->getJson('/api/cursos');
        $time1 = (microtime(true) - $startTime) * 1000;

        // Segunda consulta (con cache)
        $startTime = microtime(true);
        $response2 = $this->getJson('/api/cursos');
        $time2 = (microtime(true) - $startTime) * 1000;

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // La segunda consulta debe ser más rápida
        $this->assertLessThan($time1, $time2, 
            "La consulta con cache debe ser más rápida");
    }

    /** @test */
    public function consulta_curso_especifico_es_rapida()
    {
        Sanctum::actingAs($this->user);
        
        $curso = Curso::first();

        $startTime = microtime(true);
        
        $response = $this->getJson("/api/cursos/{$curso->id}");
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        
        // Verificar que la respuesta es rápida (< 300ms)
        $this->assertLessThan(300, $executionTime, 
            "La consulta de curso específico tardó {$executionTime}ms, debe ser menor a 300ms");
    }

    /** @test */
    public function filtros_no_afectan_rendimiento_significativamente()
    {
        Sanctum::actingAs($this->user);

        // Consulta sin filtros
        $startTime = microtime(true);
        $response1 = $this->getJson('/api/cursos');
        $time1 = (microtime(true) - $startTime) * 1000;

        // Consulta con filtros
        $startTime = microtime(true);
        $response2 = $this->getJson('/api/cursos?nivel=intermedio&instructor_id=1');
        $time2 = (microtime(true) - $startTime) * 1000;

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // La diferencia no debe ser mayor al 50%
        $difference = abs($time1 - $time2);
        $maxDifference = $time1 * 0.5;
        
        $this->assertLessThan($maxDifference, $difference, 
            "Los filtros afectan demasiado el rendimiento");
    }
} 