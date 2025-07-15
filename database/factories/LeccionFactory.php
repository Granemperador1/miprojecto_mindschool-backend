<?php

namespace Database\Factories;

use App\Models\Leccion;
use App\Models\Curso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Leccion>
 */
class LeccionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titulo' => fake()->sentence(4),
            'descripcion' => fake()->paragraph(),
            'contenido' => fake()->paragraphs(3, true),
            'duracion' => fake()->numberBetween(15, 90),
            'orden' => fake()->numberBetween(1, 20),
            'curso_id' => Curso::factory(),
            'estado' => fake()->randomElement(['activo', 'inactivo', 'borrador']),
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the leccion is active.
     */
    public function activo(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'activo',
        ]);
    }

    /**
     * Indicate that the leccion is short.
     */
    public function corta(): static
    {
        return $this->state(fn (array $attributes) => [
            'duracion' => fake()->numberBetween(5, 30),
        ]);
    }

    /**
     * Indicate that the leccion is long.
     */
    public function larga(): static
    {
        return $this->state(fn (array $attributes) => [
            'duracion' => fake()->numberBetween(60, 120),
        ]);
    }
} 