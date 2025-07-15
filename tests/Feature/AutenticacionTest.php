<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function usuario_puede_iniciar_sesion()
    {
        $usuario = User::factory()->create([
            'password' => bcrypt('secreto123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $usuario->email,
            'password' => 'secreto123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token']);
    }

    /** @test */
    public function usuario_no_autenticado_no_accede_a_ruta_protegida()
    {
        $response = $this->getJson('/api/cursos');
        $response->assertStatus(401);
    }
} 