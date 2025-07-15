<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Curso;
use App\Models\Leccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class LeccionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear un usuario con rol de profesor
        $this->user = User::factory()->create();
        $this->user->assignRole('profesor');
        
        // Crear un curso para las pruebas
        $this->curso = Curso::factory()->create();
    }

    public function test_usuario_autenticado_puede_ver_lista_de_lecciones()
    {
        Sanctum::actingAs($this->user);

        Leccion::factory()->count(3)->create(['curso_id' => $this->curso->id]);

        $response = $this->getJson('/api/lecciones');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'titulo',
                             'descripcion',
                             'contenido',
                             'duracion',
                             'orden',
                             'curso_id',
                             'estado',
                             'created_at',
                             'updated_at'
                         ]
                     ]
                 ]);
    }

    public function test_usuario_autenticado_puede_crear_leccion()
    {
        Sanctum::actingAs($this->user);

        $leccionData = [
            'titulo' => 'Test Lesson',
            'descripcion' => 'This is a test lesson description.',
            'contenido' => 'This is the lesson content.',
            'duracion' => 30,
            'orden' => 1,
            'curso_id' => $this->curso->id,
            'estado' => 'activo'
        ];

        $response = $this->postJson('/api/lecciones', $leccionData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'titulo',
                         'descripcion',
                         'contenido',
                         'duracion',
                         'orden',
                         'curso_id',
                         'estado',
                         'created_at',
                         'updated_at'
                     ]
                 ]);

        $this->assertDatabaseHas('lecciones', [
            'titulo' => 'Test Lesson',
            'curso_id' => $this->curso->id
        ]);
    }

    public function test_usuario_autenticado_puede_ver_leccion_especifica()
    {
        Sanctum::actingAs($this->user);

        $leccion = Leccion::factory()->create(['curso_id' => $this->curso->id]);

        $response = $this->getJson("/api/lecciones/{$leccion->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'titulo',
                         'descripcion',
                         'contenido',
                         'duracion',
                         'orden',
                         'curso_id',
                         'estado',
                         'created_at',
                         'updated_at'
                     ]
                 ]);
    }

    public function test_usuario_autenticado_puede_actualizar_leccion()
    {
        Sanctum::actingAs($this->user);

        $leccion = Leccion::factory()->create(['curso_id' => $this->curso->id]);

        $updateData = [
            'titulo' => 'Lección Actualizada',
            'descripcion' => 'Descripción actualizada',
            'contenido' => 'Contenido actualizado...',
            'duracion' => 45,
            'orden' => 2,
            'estado' => 'activo'
        ];

        $response = $this->putJson("/api/lecciones/{$leccion->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'titulo' => 'Lección Actualizada',
                         'descripcion' => 'Descripción actualizada'
                     ]
                 ]);

        $this->assertDatabaseHas('lecciones', [
            'id' => $leccion->id,
            'titulo' => 'Lección Actualizada'
        ]);
    }

    public function test_usuario_autenticado_puede_eliminar_leccion()
    {
        Sanctum::actingAs($this->user);

        $leccion = Leccion::factory()->create(['curso_id' => $this->curso->id]);

        $response = $this->deleteJson("/api/lecciones/{$leccion->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Lección eliminada exitosamente']);

        $this->assertDatabaseMissing('lecciones', ['id' => $leccion->id]);
    }

    public function test_usuario_autenticado_puede_ver_lecciones_por_curso()
    {
        Sanctum::actingAs($this->user);

        Leccion::factory()->count(3)->create(['curso_id' => $this->curso->id]);

        $response = $this->getJson("/api/cursos/{$this->curso->id}/lecciones");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'titulo',
                             'descripcion',
                             'contenido',
                             'duracion',
                             'orden',
                             'curso_id',
                             'estado',
                             'created_at',
                             'updated_at'
                         ]
                     ]
                 ]);
    }

    public function test_usuario_no_autenticado_no_puede_acceder_a_lecciones()
    {
        $response = $this->getJson('/api/lecciones');

        $response->assertStatus(401);
    }

    public function test_crear_leccion_falla_con_datos_invalidos()
    {
        Sanctum::actingAs($this->user);

        $leccionData = [
            'titulo' => '',
            'descripcion' => '',
            'contenido' => '',
            'duracion' => 'invalid',
            'orden' => -1,
            'curso_id' => 999, // Curso inexistente
            'estado' => 'invalid_status'
        ];

        $response = $this->postJson('/api/lecciones', $leccionData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['titulo', 'descripcion', 'contenido', 'duracion', 'orden', 'curso_id', 'estado']);
    }

    public function test_actualizar_leccion_inexistente_devuelve_404()
    {
        Sanctum::actingAs($this->user);

        $updateData = [
            'titulo' => 'Lección Actualizada',
            'descripcion' => 'Descripción actualizada'
        ];

        $response = $this->putJson('/api/lecciones/999', $updateData);

        $response->assertStatus(404);
    }

    public function test_eliminar_leccion_inexistente_devuelve_404()
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/lecciones/999');

        $response->assertStatus(404);
    }
} 