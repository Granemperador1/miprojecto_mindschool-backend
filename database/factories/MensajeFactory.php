<?php

namespace Database\Factories;

use App\Models\Mensaje;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mensaje>
 */
class MensajeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'remitente_id' => User::factory(),
            'destinatario_id' => User::factory(),
            'asunto' => fake()->sentence(),
            'contenido' => fake()->paragraphs(2, true),
            'tipo' => fake()->randomElement(['consulta', 'respuesta', 'notificacion', 'general']),
            'estado' => fake()->randomElement(['enviado', 'leido', 'archivado']),
            'fecha_envio' => fake()->dateTimeBetween('-1 month', 'now'),
            'fecha_lectura' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'updated_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate that the mensaje is sent.
     */
    public function enviado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'enviado',
            'fecha_lectura' => null,
        ]);
    }

    /**
     * Indicate that the mensaje is read.
     */
    public function leido(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'leido',
            'fecha_lectura' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the mensaje is archived.
     */
    public function archivado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'archivado',
            'fecha_lectura' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the mensaje is a consultation.
     */
    public function consulta(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'consulta',
            'asunto' => 'Consulta: ' . fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the mensaje is a response.
     */
    public function respuesta(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'respuesta',
            'asunto' => 'Re: ' . fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the mensaje is a notification.
     */
    public function notificacion(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'notificacion',
            'asunto' => 'Notificación: ' . fake()->sentence(),
        ]);
    }
} 