<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Mensaje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class MensajeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuarios para las pruebas
        $this->usuario1 = User::factory()->create();
        $this->usuario1->assignRole('estudiante');
        
        $this->usuario2 = User::factory()->create();
        $this->usuario2->assignRole('profesor');
    }

    public function test_usuario_autenticado_puede_enviar_mensaje()
    {
        Sanctum::actingAs($this->usuario1);

        $mensajeData = [
            'destinatario_id' => $this->usuario2->id,
            'asunto' => 'Test Message',
            'contenido' => 'This is a test message content.',
            'tipo' => 'general',
            'estado' => 'enviado'
        ];

        $response = $this->postJson('/api/mensajes', $mensajeData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'remitente_id',
                         'destinatario_id',
                         'asunto',
                         'contenido',
                         'tipo',
                         'estado',
                         'fecha_envio',
                         'created_at',
                         'updated_at'
                     ]
                 ]);

        $this->assertDatabaseHas('mensajes', [
            'remitente_id' => $this->usuario1->id,
            'destinatario_id' => $this->usuario2->id,
            'asunto' => 'Test Message'
        ]);
    }

    public function test_usuario_autenticado_puede_ver_sus_mensajes_enviados()
    {
        Sanctum::actingAs($this->usuario1);

        Mensaje::factory()->count(3)->create(['remitente_id' => $this->usuario1->id]);

        $response = $this->getJson('/api/mensajes/enviados');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'remitente_id',
                             'destinatario_id',
                             'asunto',
                             'contenido',
                             'tipo',
                             'estado',
                             'fecha_envio',
                             'fecha_lectura',
                             'created_at',
                             'updated_at'
                         ]
                     ]
                 ]);
    }

    public function test_usuario_autenticado_puede_ver_sus_mensajes_recibidos()
    {
        Sanctum::actingAs($this->usuario2);

        Mensaje::factory()->count(3)->create(['destinatario_id' => $this->usuario2->id]);

        $response = $this->getJson('/api/mensajes/recibidos');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'remitente_id',
                             'destinatario_id',
                             'asunto',
                             'contenido',
                             'tipo',
                             'estado',
                             'fecha_envio',
                             'fecha_lectura',
                             'created_at',
                             'updated_at'
                         ]
                     ]
                 ]);
    }

    public function test_usuario_autenticado_puede_ver_mensaje_especifico()
    {
        Sanctum::actingAs($this->usuario1);

        $mensaje = Mensaje::factory()->create(['remitente_id' => $this->usuario1->id]);

        $response = $this->getJson("/api/mensajes/{$mensaje->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'remitente_id',
                         'destinatario_id',
                         'asunto',
                         'contenido',
                         'tipo',
                         'estado',
                         'fecha_envio',
                         'fecha_lectura',
                         'created_at',
                         'updated_at'
                     ]
                 ]);
    }

    public function test_usuario_puede_marcar_mensaje_como_leido()
    {
        Sanctum::actingAs($this->usuario2);

        $mensaje = Mensaje::factory()->create([
            'destinatario_id' => $this->usuario2->id,
            'estado' => 'enviado',
            'fecha_lectura' => null
        ]);

        $response = $this->putJson("/api/mensajes/{$mensaje->id}/leer");

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'estado' => 'leido',
                         'fecha_lectura' => now()->toDateString()
                     ]
                 ]);

        $this->assertDatabaseHas('mensajes', [
            'id' => $mensaje->id,
            'estado' => 'leido'
        ]);
    }

    public function test_usuario_puede_eliminar_mensaje()
    {
        Sanctum::actingAs($this->usuario1);

        $mensaje = Mensaje::factory()->create(['remitente_id' => $this->usuario1->id]);

        $response = $this->deleteJson("/api/mensajes/{$mensaje->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Mensaje eliminado exitosamente']);

        $this->assertDatabaseMissing('mensajes', ['id' => $mensaje->id]);
    }

    public function test_usuario_puede_responder_mensaje()
    {
        Sanctum::actingAs($this->usuario2);

        $mensajeOriginal = Mensaje::factory()->create([
            'remitente_id' => $this->usuario1->id,
            'destinatario_id' => $this->usuario2->id
        ]);

        $respuestaData = [
            'destinatario_id' => $this->usuario1->id,
            'asunto' => 'Re: ' . $mensajeOriginal->asunto,
            'contenido' => 'Gracias por tu consulta. Te respondo a continuación...',
            'tipo' => 'respuesta',
            'estado' => 'enviado'
        ];

        $response = $this->postJson('/api/mensajes', $respuestaData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'remitente_id',
                         'destinatario_id',
                         'asunto',
                         'contenido',
                         'tipo',
                         'estado',
                         'fecha_envio',
                         'created_at',
                         'updated_at'
                     ]
                 ]);
    }

    public function test_usuario_no_autenticado_no_puede_acceder_a_mensajes()
    {
        $response = $this->getJson('/api/mensajes');

        $response->assertStatus(401);
    }

    public function test_enviar_mensaje_falla_con_datos_invalidos()
    {
        Sanctum::actingAs($this->usuario1);

        $mensajeData = [
            'destinatario_id' => 999, // Usuario inexistente
            'asunto' => '',
            'contenido' => '',
            'tipo' => 'invalid_type',
            'estado' => 'invalid_status'
        ];

        $response = $this->postJson('/api/mensajes', $mensajeData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['destinatario_id', 'asunto', 'contenido', 'tipo', 'estado']);
    }

    public function test_usuario_no_puede_enviar_mensaje_a_si_mismo()
    {
        Sanctum::actingAs($this->usuario1);

        $mensajeData = [
            'destinatario_id' => $this->usuario1->id,
            'asunto' => 'Mensaje a mí mismo',
            'contenido' => 'Este mensaje no debería enviarse.',
            'tipo' => 'consulta',
            'estado' => 'enviado'
        ];

        $response = $this->postJson('/api/mensajes', $mensajeData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['destinatario_id']);
    }

    public function test_usuario_no_puede_ver_mensajes_de_otros_usuarios()
    {
        Sanctum::actingAs($this->usuario1);

        $otroUsuario = User::factory()->create();
        $mensajeOtro = Mensaje::factory()->create([
            'remitente_id' => $otroUsuario->id,
            'destinatario_id' => $this->usuario2->id
        ]);

        $response = $this->getJson("/api/mensajes/{$mensajeOtro->id}");

        $response->assertStatus(403);
    }

    public function test_actualizar_mensaje_inexistente_devuelve_404()
    {
        Sanctum::actingAs($this->usuario1);

        $response = $this->putJson('/api/mensajes/999/leer');

        $response->assertStatus(404);
    }

    public function test_eliminar_mensaje_inexistente_devuelve_404()
    {
        Sanctum::actingAs($this->usuario1);

        $response = $this->deleteJson('/api/mensajes/999');

        $response->assertStatus(404);
    }

    public function test_mensajes_se_ordenan_por_fecha_de_envio()
    {
        Sanctum::actingAs($this->usuario1);

        // Crear mensajes con diferentes fechas
        $mensaje1 = Mensaje::factory()->create([
            'remitente_id' => $this->usuario1->id,
            'fecha_envio' => now()->subDays(2)
        ]);

        $mensaje2 = Mensaje::factory()->create([
            'remitente_id' => $this->usuario1->id,
            'fecha_envio' => now()
        ]);

        $mensaje3 = Mensaje::factory()->create([
            'remitente_id' => $this->usuario1->id,
            'fecha_envio' => now()->subDay()
        ]);

        $response = $this->getJson('/api/mensajes/enviados');

        $response->assertStatus(200);

        $data = $response->json('data');
        // Los mensajes deberían estar ordenados por fecha_envio descendente (más reciente primero)
        $this->assertEquals($mensaje2->id, $data[0]['id']);
        $this->assertEquals($mensaje3->id, $data[1]['id']);
        $this->assertEquals($mensaje1->id, $data[2]['id']);
    }
} 