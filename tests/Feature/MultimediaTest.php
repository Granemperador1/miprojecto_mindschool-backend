<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Curso;
use App\Models\Leccion;
use App\Models\Multimedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class MultimediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear un usuario con rol de profesor
        $this->user = User::factory()->create();
        $this->user->assignRole('profesor');
        
        // Crear un curso y lección para las pruebas
        $this->curso = Curso::factory()->create();
        $this->leccion = Leccion::factory()->create(['curso_id' => $this->curso->id]);
        
        // Configurar storage para pruebas
        Storage::fake('public');
    }

    public function test_profesor_puede_subir_archivo_multimedia()
    {
        Sanctum::actingAs($this->user);

        $file = UploadedFile::fake()->create('video.mp4', 1024);

        $multimediaData = [
            'titulo' => 'Video de introducción',
            'descripcion' => 'Video explicativo de la lección',
            'tipo' => 'video',
            'url' => $file,
            'leccion_id' => $this->leccion->id,
            'orden' => 1,
            'estado' => 'activo'
        ];

        $response = $this->postJson('/api/multimedia', $multimediaData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'titulo',
                         'descripcion',
                         'tipo',
                         'url',
                         'leccion_id',
                         'orden',
                         'estado',
                         'created_at',
                         'updated_at'
                     ]
                 ]);

        $this->assertDatabaseHas('multimedia', [
            'titulo' => 'Video de introducción',
            'tipo' => 'video',
            'leccion_id' => $this->leccion->id
        ]);
    }

    public function test_usuario_autenticado_puede_ver_lista_de_multimedia()
    {
        Sanctum::actingAs($this->user);

        Multimedia::factory()->count(3)->create(['leccion_id' => $this->leccion->id]);

        $response = $this->getJson('/api/multimedia');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'titulo',
                             'descripcion',
                             'tipo',
                             'url',
                             'leccion_id',
                             'orden',
                             'estado',
                             'created_at',
                             'updated_at'
                         ]
                     ]
                 ]);
    }

    public function test_usuario_autenticado_puede_ver_multimedia_especifico()
    {
        Sanctum::actingAs($this->user);

        $multimedia = Multimedia::factory()->create(['leccion_id' => $this->leccion->id]);

        $response = $this->getJson("/api/multimedia/{$multimedia->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'titulo',
                         'descripcion',
                         'tipo',
                         'url',
                         'leccion_id',
                         'orden',
                         'estado',
                         'created_at',
                         'updated_at'
                     ]
                 ]);
    }

    public function test_profesor_puede_actualizar_multimedia()
    {
        Sanctum::actingAs($this->user);

        $multimedia = Multimedia::factory()->create(['leccion_id' => $this->leccion->id]);

        $updateData = [
            'titulo' => 'Video actualizado',
            'descripcion' => 'Descripción actualizada',
            'orden' => 2,
            'estado' => 'activo'
        ];

        $response = $this->putJson("/api/multimedia/{$multimedia->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'titulo' => 'Video actualizado',
                         'descripcion' => 'Descripción actualizada'
                     ]
                 ]);

        $this->assertDatabaseHas('multimedia', [
            'id' => $multimedia->id,
            'titulo' => 'Video actualizado'
        ]);
    }

    public function test_profesor_puede_eliminar_multimedia()
    {
        Sanctum::actingAs($this->user);

        $multimedia = Multimedia::factory()->create(['leccion_id' => $this->leccion->id]);

        $response = $this->deleteJson("/api/multimedia/{$multimedia->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Multimedia eliminado exitosamente']);

        $this->assertDatabaseMissing('multimedia', ['id' => $multimedia->id]);
    }

    public function test_usuario_autenticado_puede_ver_multimedia_por_leccion()
    {
        Sanctum::actingAs($this->user);

        Multimedia::factory()->count(3)->create(['leccion_id' => $this->leccion->id]);

        $response = $this->getJson("/api/lecciones/{$this->leccion->id}/multimedia");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'titulo',
                             'descripcion',
                             'tipo',
                             'url',
                             'leccion_id',
                             'orden',
                             'estado',
                             'created_at',
                             'updated_at'
                         ]
                     ]
                 ]);
    }

    public function test_usuario_no_autenticado_no_puede_acceder_a_multimedia()
    {
        $response = $this->getJson('/api/multimedia');

        $response->assertStatus(401);
    }

    public function test_subir_multimedia_falla_con_datos_invalidos()
    {
        Sanctum::actingAs($this->user);

        $multimediaData = [
            'titulo' => '',
            'descripcion' => '',
            'tipo' => 'invalid_type',
            'leccion_id' => 999, // Lección inexistente
            'orden' => -1,
            'estado' => 'invalid_status'
        ];

        $response = $this->postJson('/api/multimedia', $multimediaData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['titulo', 'tipo', 'leccion_id', 'orden', 'estado']);
    }

    public function test_subir_archivo_con_tipo_no_permitido_falla()
    {
        Sanctum::actingAs($this->user);

        $file = UploadedFile::fake()->create('document.exe', 1024);

        $multimediaData = [
            'titulo' => 'Archivo ejecutable',
            'descripcion' => 'Archivo no permitido',
            'tipo' => 'documento',
            'url' => $file,
            'leccion_id' => $this->leccion->id,
            'orden' => 1,
            'estado' => 'activo'
        ];

        $response = $this->postJson('/api/multimedia', $multimediaData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['url']);
    }

    public function test_actualizar_multimedia_inexistente_devuelve_404()
    {
        Sanctum::actingAs($this->user);

        $updateData = [
            'titulo' => 'Multimedia actualizado',
            'descripcion' => 'Descripción actualizada'
        ];

        $response = $this->putJson('/api/multimedia/999', $updateData);

        $response->assertStatus(404);
    }

    public function test_eliminar_multimedia_inexistente_devuelve_404()
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/multimedia/999');

        $response->assertStatus(404);
    }

    public function test_multimedia_se_ordena_correctamente()
    {
        Sanctum::actingAs($this->user);

        // Crear multimedia con diferentes órdenes
        $multimedia1 = Multimedia::factory()->create([
            'leccion_id' => $this->leccion->id,
            'orden' => 3
        ]);

        $multimedia2 = Multimedia::factory()->create([
            'leccion_id' => $this->leccion->id,
            'orden' => 1
        ]);

        $multimedia3 = Multimedia::factory()->create([
            'leccion_id' => $this->leccion->id,
            'orden' => 2
        ]);

        $response = $this->getJson("/api/lecciones/{$this->leccion->id}/multimedia");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(1, $data[0]['orden']);
        $this->assertEquals(2, $data[1]['orden']);
        $this->assertEquals(3, $data[2]['orden']);
    }
} 