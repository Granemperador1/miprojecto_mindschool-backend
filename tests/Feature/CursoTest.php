<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Curso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class CursoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear un usuario con rol de profesor
        $this->user = User::factory()->create();
        $this->user->assignRole('profesor');
    }

    public function test_usuario_autenticado_puede_ver_lista_de_cursos()
    {
        Sanctum::actingAs($this->user);

        Curso::factory()->count(3)->create();

        $response = $this->getJson('/api/cursos');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'titulo',
                             'descripcion',
                             'duracion',
                             'nivel',
                             'precio',
                             'estado',
                             'created_at',
                             'updated_at'
                         ]
                     ]
                 ]);
    }

    public function test_usuario_autenticado_puede_crear_curso()
    {
        Sanctum::actingAs($this->user);

        $cursoData = [
            'titulo' => 'Test Course',
            'descripcion' => 'This is a test course description.',
            'duracion' => 60,
            'nivel' => 'intermedio',
            'precio' => 99.99,
            'estado' => 'activo',
            'instructor_id' => $this->user->id
        ];

        $response = $this->postJson('/api/cursos', $cursoData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'titulo',
                         'descripcion',
                         'duracion',
                         'nivel',
                         'precio',
                         'estado',
                         'created_at',
                         'updated_at'
                     ]
                 ]);

        $this->assertDatabaseHas('cursos', [
            'titulo' => 'Test Course',
            'descripcion' => 'This is a test course description.'
        ]);
    }

    public function test_usuario_autenticado_puede_ver_curso_especifico()
    {
        Sanctum::actingAs($this->user);

        $curso = Curso::factory()->create();

        $response = $this->getJson("/api/cursos/{$curso->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'titulo',
                         'descripcion',
                         'duracion',
                         'nivel',
                         'precio',
                         'estado',
                         'created_at',
                         'updated_at'
                     ]
                 ]);
    }

    public function test_usuario_autenticado_puede_actualizar_curso()
    {
        Sanctum::actingAs($this->user);

        $curso = Curso::factory()->create();

        $updateData = [
            'titulo' => 'Curso Actualizado',
            'descripcion' => 'Descripción actualizada',
            'duracion' => 50,
            'nivel' => 'avanzado',
            'precio' => 149.99,
            'estado' => 'activo'
        ];

        $response = $this->putJson("/api/cursos/{$curso->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'titulo' => 'Curso Actualizado',
                         'descripcion' => 'Descripción actualizada'
                     ]
                 ]);

        $this->assertDatabaseHas('cursos', [
            'id' => $curso->id,
            'titulo' => 'Curso Actualizado'
        ]);
    }

    public function test_usuario_autenticado_puede_eliminar_curso()
    {
        Sanctum::actingAs($this->user);

        $curso = Curso::factory()->create();

        $response = $this->deleteJson("/api/cursos/{$curso->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Curso eliminado exitosamente']);

        $this->assertDatabaseMissing('cursos', ['id' => $curso->id]);
    }

    public function test_usuario_no_autenticado_no_puede_acceder_a_cursos()
    {
        $response = $this->getJson('/api/cursos');

        $response->assertStatus(401);
    }

    public function test_crear_curso_falla_con_datos_invalidos()
    {
        Sanctum::actingAs($this->user);

        $cursoData = [
            'titulo' => '',
            'descripcion' => '',
            'duracion' => 'invalid',
            'nivel' => 'invalid_level',
            'precio' => -10,
            'estado' => 'invalid_status'
        ];

        $response = $this->postJson('/api/cursos', $cursoData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['titulo', 'descripcion', 'duracion', 'nivel', 'precio', 'estado']);
    }

    public function test_actualizar_curso_inexistente_devuelve_404()
    {
        Sanctum::actingAs($this->user);

        $updateData = [
            'titulo' => 'Curso Actualizado',
            'descripcion' => 'Descripción actualizada'
        ];

        $response = $this->putJson('/api/cursos/999', $updateData);

        $response->assertStatus(404);
    }

    public function test_eliminar_curso_inexistente_devuelve_404()
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/cursos/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function admin_puede_crear_listar_editar_y_eliminar_curso()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        // Crear
        $response = $this->postJson('/api/cursos', [
            'titulo' => 'Curso Test',
            'descripcion' => 'Descripción de prueba',
            'duracion' => 30,
            'nivel' => 'principiante',
            'precio' => 0,
            'estado' => 'activo',
            'instructor_id' => $admin->id
        ]);
        $response->assertStatus(201);
        $cursoId = $response->json('data.id');

        // Listar
        $response = $this->getJson('/api/cursos');
        $response->assertStatus(200)
                 ->assertJsonFragment(['titulo' => 'Curso Test']);

        // Editar
        $response = $this->putJson("/api/cursos/{$cursoId}", [
            'titulo' => 'Curso Editado',
            'descripcion' => 'Editado',
        ]);
        $response->assertStatus(200)
                 ->assertJsonFragment(['titulo' => 'Curso Editado']);

        // Eliminar
        $response = $this->deleteJson("/api/cursos/{$cursoId}");
        $response->assertStatus(200);
    }
} 