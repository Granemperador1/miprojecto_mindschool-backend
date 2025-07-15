<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_puede_registrarse()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'user' => [
                         'id',
                         'name',
                         'email',
                         'roles',
                         'created_at',
                         'updated_at'
                     ],
                     'token'
                 ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        // Verificar que se asignó el rol de estudiante por defecto
        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue($user->hasRole('estudiante'));
    }

    public function test_usuario_puede_hacer_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);
        $user->assignRole('estudiante');

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'user' => [
                         'id',
                         'name',
                         'email',
                         'roles',
                         'created_at',
                         'updated_at'
                     ],
                     'token'
                 ]);

        // Verificar que los roles se incluyen en la respuesta
        $response->assertJson([
            'user' => [
                'roles' => ['estudiante']
            ]
        ]);
    }

    public function test_usuario_puede_hacer_logout()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Sesión cerrada exitosamente']);
    }

    public function test_registro_falla_con_datos_invalidos()
    {
        $userData = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_login_falla_con_credenciales_invalidas()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Credenciales inválidas']);
    }

    public function test_usuario_no_autenticado_no_accede_a_ruta_protegida()
    {
        $response = $this->getJson('/api/cursos');
        $response->assertStatus(401);
    }

    public function test_usuario_estudiante_no_accede_a_rutas_de_admin()
    {
        $user = User::factory()->create();
        $user->assignRole('estudiante');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/usuarios');
        $response->assertStatus(403);
    }

    public function test_usuario_admin_puede_acceder_a_rutas_de_admin()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/usuarios');
        $response->assertStatus(200);
    }
} 