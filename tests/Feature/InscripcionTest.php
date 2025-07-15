<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Curso;
use App\Models\Inscripcion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class InscripcionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuarios con diferentes roles
        $this->estudiante = User::factory()->create();
        $this->estudiante->assignRole('estudiante');
        
        $this->instructor = User::factory()->create();
        $this->instructor->assignRole('profesor');
        
        // Crear un curso para las pruebas
        $this->curso = Curso::factory()->create();
    }

    public function test_estudiante_puede_inscribirse_en_curso()
    {
        Sanctum::actingAs($this->estudiante);

        $inscripcionData = [
            'user_id' => $this->estudiante->id,
            'curso_id' => $this->curso->id,
            'estado' => 'activo',
            'progreso' => 0
        ];

        $response = $this->postJson('/api/inscripciones', $inscripcionData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'user_id',
                         'curso_id',
                         'estado',
                         'fecha_inscripcion',
                         'progreso',
                         'created_at',
                         'updated_at'
                     ]
                 ]);

        $this->assertDatabaseHas('inscripciones', [
            'user_id' => $this->estudiante->id,
            'curso_id' => $this->curso->id,
            'estado' => 'activo'
        ]);
    }

    public function test_estudiante_puede_ver_sus_inscripciones()
    {
        Sanctum::actingAs($this->estudiante);

        Inscripcion::factory()->count(3)->create(['user_id' => $this->estudiante->id]);

        $response = $this->getJson('/api/inscripciones');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'user_id',
                             'curso_id',
                             'estado',
                             'fecha_inscripcion',
                             'progreso',
                             'created_at',
                             'updated_at'
                         ]
                     ]
                 ]);
    }

    public function test_estudiante_puede_ver_inscripcion_especifica()
    {
        Sanctum::actingAs($this->estudiante);

        $inscripcion = Inscripcion::factory()->create(['user_id' => $this->estudiante->id]);

        $response = $this->getJson("/api/inscripciones/{$inscripcion->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'user_id',
                         'curso_id',
                         'estado',
                         'fecha_inscripcion',
                         'progreso',
                         'created_at',
                         'updated_at'
                     ]
                 ]);
    }

    public function test_estudiante_puede_actualizar_progreso_de_inscripcion()
    {
        Sanctum::actingAs($this->estudiante);

        $inscripcion = Inscripcion::factory()->create([
            'user_id' => $this->estudiante->id,
            'progreso' => 25
        ]);

        $updateData = [
            'progreso' => 50,
            'estado' => 'en_progreso'
        ];

        $response = $this->putJson("/api/inscripciones/{$inscripcion->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'progreso' => 50,
                         'estado' => 'en_progreso'
                     ]
                 ]);

        $this->assertDatabaseHas('inscripciones', [
            'id' => $inscripcion->id,
            'progreso' => 50,
            'estado' => 'en_progreso'
        ]);
    }

    public function test_estudiante_puede_cancelar_inscripcion()
    {
        Sanctum::actingAs($this->estudiante);

        $inscripcion = Inscripcion::factory()->create(['user_id' => $this->estudiante->id]);

        $response = $this->deleteJson("/api/inscripciones/{$inscripcion->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Inscripción cancelada exitosamente']);

        $this->assertDatabaseMissing('inscripciones', ['id' => $inscripcion->id]);
    }

    public function test_profesor_puede_ver_inscripciones_de_su_curso()
    {
        Sanctum::actingAs($this->instructor);

        // Asignar el curso al instructor
        $this->curso->update(['instructor_id' => $this->instructor->id]);

        Inscripcion::factory()->count(3)->create(['curso_id' => $this->curso->id]);

        $response = $this->getJson("/api/cursos/{$this->curso->id}/inscripciones");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'user_id',
                             'curso_id',
                             'estado',
                             'fecha_inscripcion',
                             'progreso',
                             'created_at',
                             'updated_at'
                         ]
                     ]
                 ]);
    }

    public function test_usuario_no_autenticado_no_puede_acceder_a_inscripciones()
    {
        $response = $this->getJson('/api/inscripciones');

        $response->assertStatus(401);
    }

    public function test_inscribirse_falla_con_datos_invalidos()
    {
        Sanctum::actingAs($this->estudiante);

        $inscripcionData = [
            'curso_id' => 999, // Curso inexistente
            'estado' => 'invalid_status',
            'fecha_inscripcion' => 'invalid_date',
            'progreso' => 150 // Progreso inválido
        ];

        $response = $this->postJson('/api/inscripciones', $inscripcionData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['curso_id', 'estado', 'fecha_inscripcion', 'progreso']);
    }

    public function test_estudiante_no_puede_inscribirse_dos_veces_en_mismo_curso()
    {
        Sanctum::actingAs($this->estudiante);

        // Primera inscripción
        Inscripcion::factory()->create([
            'user_id' => $this->estudiante->id,
            'curso_id' => $this->curso->id
        ]);

        // Intentar segunda inscripción
        $inscripcionData = [
            'user_id' => $this->estudiante->id,
            'curso_id' => $this->curso->id,
            'estado' => 'activo',
            'progreso' => 0
        ];

        $response = $this->postJson('/api/inscripciones', $inscripcionData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['curso_id']);
    }

    public function test_actualizar_inscripcion_inexistente_devuelve_404()
    {
        Sanctum::actingAs($this->estudiante);

        $updateData = [
            'progreso' => 75,
            'estado' => 'completado'
        ];

        $response = $this->putJson('/api/inscripciones/999', $updateData);

        $response->assertStatus(404);
    }

    public function test_estudiante_no_puede_ver_inscripciones_de_otros_usuarios()
    {
        Sanctum::actingAs($this->estudiante);

        $otroEstudiante = User::factory()->create();
        $otroEstudiante->assignRole('estudiante');

        $inscripcionOtro = Inscripcion::factory()->create(['user_id' => $otroEstudiante->id]);

        $response = $this->getJson("/api/inscripciones/{$inscripcionOtro->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function usuario_puede_inscribirse_listar_y_eliminar_inscripcion()
    {
        $usuario = User::factory()->create();
        $curso = Curso::factory()->create();
        $this->actingAs($usuario);

        // Inscribir
        $response = $this->postJson('/api/inscripciones', [
            'user_id' => $usuario->id,
            'curso_id' => $curso->id,
            'estado' => 'activo',
            'progreso' => 0
        ]);
        $response->assertStatus(201);
        $inscripcionId = $response->json('data.id');

        // Listar
        $response = $this->getJson('/api/inscripciones');
        $response->assertStatus(200)
                 ->assertJsonFragment(['curso_id' => $curso->id]);

        // Eliminar
        $response = $this->deleteJson("/api/inscripciones/{$inscripcionId}");
        $response->assertStatus(200);
    }
} 