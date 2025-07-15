<?php

namespace Database\Factories;

use App\Models\Curso;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Curso>
 */
class CursoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titulo' => fake()->sentence(3),
            'descripcion' => fake()->paragraph(),
            'duracion' => fake()->numberBetween(10, 120),
            'nivel' => fake()->randomElement(['principiante', 'intermedio', 'avanzado']),
            'precio' => fake()->randomFloat(2, 0, 500),
            'estado' => fake()->randomElement(['activo', 'inactivo', 'borrador']),
            'instructor_id' => User::factory(),
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the curso is active.
     */
    public function activo(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'activo',
        ]);
    }

    /**
     * Indicate that the curso is free.
     */
    public function gratuito(): static
    {
        return $this->state(fn (array $attributes) => [
            'precio' => 0,
        ]);
    }

    /**
     * Indicate that the curso is for beginners.
     */
    public function principiante(): static
    {
        return $this->state(fn (array $attributes) => [
            'nivel' => 'principiante',
        ]);
    }
} 