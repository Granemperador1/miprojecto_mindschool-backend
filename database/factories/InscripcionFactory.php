<?php

namespace Database\Factories;

use App\Models\Inscripcion;
use App\Models\User;
use App\Models\Curso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inscripcion>
 */
class InscripcionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'curso_id' => Curso::factory(),
            'estado' => fake()->randomElement(['activo', 'completado', 'cancelado', 'en_progreso']),
            'fecha_inscripcion' => fake()->dateTimeBetween('-6 months', 'now'),
            'progreso' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the inscripcion is active.
     */
    public function activo(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'activo',
            'progreso' => fake()->numberBetween(0, 50),
        ]);
    }

    /**
     * Indicate that the inscripcion is completed.
     */
    public function completado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'completado',
            'progreso' => 100,
        ]);
    }

    /**
     * Indicate that the inscripcion is in progress.
     */
    public function enProgreso(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'en_progreso',
            'progreso' => fake()->numberBetween(25, 75),
        ]);
    }

    /**
     * Indicate that the inscripcion is cancelled.
     */
    public function cancelado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'cancelado',
            'progreso' => fake()->numberBetween(0, 30),
        ]);
    }
} 