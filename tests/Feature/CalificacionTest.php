<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Curso;
use App\Models\Leccion;
use App\Models\Calificacion;

class CalificacionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $profesor;
    protected $estudiante;
    protected $curso;
    protected $leccion;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Limpiar el cache de permisos de Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Crear roles
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        
        // Crear profesor
        $this->profesor = User::factory()->create();
        $this->profesor->assignRole('profesor');
        $this->profesor->refresh();
        
        // Crear estudiante
        $this->estudiante = User::factory()->create();
        $this->estudiante->assignRole('estudiante');
        
        // Crear curso
        $this->curso = Curso::factory()->create([
            'instructor_id' => $this->profesor->id
        ]);
        
        // Crear lección
        $this->leccion = Leccion::factory()->create([
            'curso_id' => $this->curso->id
        ]);
    }

    /** @test */
    public function profesor_puede_crear_calificacion()
    {
        $response = $this->actingAs($this->profesor, 'web')
            ->postJson('/api/calificaciones', [
                'estudiante_id' => $this->estudiante->id,
                'curso_id' => $this->curso->id,
                'leccion_id' => $this->leccion->id,
                'tipo_evaluacion' => 'tarea',
                'calificacion' => 85.5,
                'peso' => 0.15,
                'comentarios' => 'Excelente trabajo',
                'estado' => 'publicada'
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'estudiante_id',
                    'curso_id',
                    'leccion_id',
                    'tipo_evaluacion',
                    'calificacion',
                    'peso',
                    'comentarios',
                    'estado',
                    'evaluador_id'
                ]
            ]);

        $this->assertDatabaseHas('calificaciones', [
            'estudiante_id' => $this->estudiante->id,
            'curso_id' => $this->curso->id,
            'calificacion' => 85.5,
            'tipo_evaluacion' => 'tarea'
        ]);
    }

    /** @test */
    public function profesor_puede_listar_calificaciones()
    {
        // Crear algunas calificaciones
        Calificacion::factory()->count(3)->create([
            'estudiante_id' => $this->estudiante->id,
            'curso_id' => $this->curso->id,
            'evaluador_id' => $this->profesor->id
        ]);

        $response = $this->actingAs($this->profesor, 'web')
            ->getJson('/api/calificaciones');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'estudiante_id',
                            'curso_id',
                            'tipo_evaluacion',
                            'calificacion',
                            'estado'
                        ]
                    ]
                ]
            ]);
    }

    /** @test */
    public function profesor_puede_actualizar_calificacion()
    {
        $calificacion = Calificacion::factory()->create([
            'estudiante_id' => $this->estudiante->id,
            'curso_id' => $this->curso->id,
            'evaluador_id' => $this->profesor->id,
            'calificacion' => 80.0
        ]);

        $response = $this->actingAs($this->profesor, 'web')
            ->putJson("/api/calificaciones/{$calificacion->id}", [
                'calificacion' => 90.0,
                'comentarios' => 'Calificación actualizada',
                'estado' => 'revisada'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'calificacion' => '90.00',
                    'comentarios' => 'Calificación actualizada',
                    'estado' => 'revisada'
                ]
            ]);

        $this->assertDatabaseHas('calificaciones', [
            'id' => $calificacion->id,
            'calificacion' => 90.0
        ]);
    }

    /** @test */
    public function profesor_puede_eliminar_calificacion()
    {
        $calificacion = Calificacion::factory()->create([
            'estudiante_id' => $this->estudiante->id,
            'curso_id' => $this->curso->id,
            'evaluador_id' => $this->profesor->id
        ]);

        $response = $this->actingAs($this->profesor, 'web')
            ->deleteJson("/api/calificaciones/{$calificacion->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('calificaciones', ['id' => $calificacion->id]);
    }

    /** @test */
    public function estudiante_puede_ver_sus_calificaciones()
    {
        // Crear calificaciones para el estudiante
        Calificacion::factory()->count(2)->create([
            'estudiante_id' => $this->estudiante->id,
            'curso_id' => $this->curso->id,
            'evaluador_id' => $this->profesor->id,
            'estado' => 'publicada'
        ]);

        $response = $this->actingAs($this->estudiante, 'web')
            ->getJson("/api/estudiantes/{$this->estudiante->id}/calificaciones");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'estudiante_id',
                        'curso_id',
                        'tipo_evaluacion',
                        'calificacion',
                        'estado'
                    ]
                ]
            ]);
    }

    /** @test */
    public function profesor_puede_ver_calificaciones_de_un_curso()
    {
        // Crear calificaciones para el curso
        Calificacion::factory()->count(3)->create([
            'curso_id' => $this->curso->id,
            'evaluador_id' => $this->profesor->id,
            'estado' => 'publicada'
        ]);

        $response = $this->actingAs($this->profesor, 'web')
            ->getJson("/api/cursos/{$this->curso->id}/calificaciones");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'estudiante_id',
                        'curso_id',
                        'tipo_evaluacion',
                        'calificacion',
                        'estado'
                    ]
                ]
            ]);
    }

    /** @test */
    public function profesor_puede_obtener_promedio_de_estudiante()
    {
        // Crear calificaciones con diferentes valores
        Calificacion::factory()->create([
            'estudiante_id' => $this->estudiante->id,
            'curso_id' => $this->curso->id,
            'evaluador_id' => $this->profesor->id,
            'calificacion' => 80.0,
            'estado' => 'publicada'
        ]);

        Calificacion::factory()->create([
            'estudiante_id' => $this->estudiante->id,
            'curso_id' => $this->curso->id,
            'evaluador_id' => $this->profesor->id,
            'calificacion' => 90.0,
            'estado' => 'publicada'
        ]);

        $response = $this->actingAs($this->profesor, 'web')
            ->getJson("/api/estudiantes/{$this->estudiante->id}/cursos/{$this->curso->id}/promedio");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'promedio',
                    'total_calificaciones'
                ]
            ])
            ->assertJson([
                'data' => [
                    'promedio' => 85.0,
                    'total_calificaciones' => 2
                ]
            ]);
    }

    /** @test */
    public function profesor_puede_publicar_calificaciones()
    {
        // Crear calificaciones en estado borrador
        $calificacion1 = Calificacion::factory()->create([
            'estudiante_id' => $this->estudiante->id,
            'curso_id' => $this->curso->id,
            'evaluador_id' => $this->profesor->id,
            'estado' => 'borrador'
        ]);

        $calificacion2 = Calificacion::factory()->create([
            'estudiante_id' => $this->estudiante->id,
            'curso_id' => $this->curso->id,
            'evaluador_id' => $this->profesor->id,
            'estado' => 'borrador'
        ]);

        $response = $this->actingAs($this->profesor, 'web')
            ->postJson('/api/calificaciones/publicar', [
                'calificacion_ids' => [$calificacion1->id, $calificacion2->id]
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('calificaciones', [
            'id' => $calificacion1->id,
            'estado' => 'publicada'
        ]);

        $this->assertDatabaseHas('calificaciones', [
            'id' => $calificacion2->id,
            'estado' => 'publicada'
        ]);
    }

    /** @test */
    public function validacion_falla_con_datos_invalidos()
    {
        $response = $this->actingAs($this->profesor, 'web')
            ->postJson('/api/calificaciones', [
                'estudiante_id' => 999, // ID inexistente
                'curso_id' => $this->curso->id,
                'tipo_evaluacion' => 'tipo_invalido',
                'calificacion' => 150, // Calificación fuera de rango
                'peso' => 2.0 // Peso fuera de rango
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'estudiante_id',
                'tipo_evaluacion',
                'calificacion',
                'peso'
            ]);
    }

    /** @test */
    public function usuario_no_autenticado_no_puede_acceder_a_calificaciones()
    {
        $response = $this->getJson('/api/calificaciones');
        $response->assertStatus(401);
    }

    /** @test */
    public function estudiante_no_puede_crear_calificaciones()
    {
        $response = $this->actingAs($this->estudiante, 'web')
            ->postJson('/api/calificaciones', [
                'estudiante_id' => $this->estudiante->id,
                'curso_id' => $this->curso->id,
                'tipo_evaluacion' => 'tarea',
                'calificacion' => 85.0,
                'peso' => 0.15
            ]);

        $response->assertStatus(403);
    }
}
