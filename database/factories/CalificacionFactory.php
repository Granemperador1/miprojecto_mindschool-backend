<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Curso;
use App\Models\Leccion;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Calificacion>
 */
class CalificacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tiposEvaluacion = ['tarea', 'examen', 'proyecto', 'participacion', 'quiz', 'trabajo_final'];
        $estados = ['borrador', 'publicada', 'revisada'];

        return [
            'estudiante_id' => User::factory(),
            'curso_id' => Curso::factory(),
            'leccion_id' => Leccion::factory(),
            'tipo_evaluacion' => $this->faker->randomElement($tiposEvaluacion),
            'calificacion' => $this->faker->randomFloat(2, 60, 100),
            'peso' => $this->faker->randomFloat(2, 0.05, 0.30),
            'comentarios' => $this->faker->optional()->sentence(),
            'fecha_evaluacion' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'evaluador_id' => User::factory(),
            'estado' => $this->faker->randomElement($estados),
        ];
    }

    /**
     * Indicate that the calificación is publicada.
     */
    public function publicada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'publicada',
        ]);
    }

    /**
     * Indicate that the calificación is for a tarea.
     */
    public function tarea(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_evaluacion' => 'tarea',
        ]);
    }

    /**
     * Indicate that the calificación is for an examen.
     */
    public function examen(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_evaluacion' => 'examen',
        ]);
    }

    /**
     * Indicate that the calificación is for a proyecto.
     */
    public function proyecto(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_evaluacion' => 'proyecto',
        ]);
    }
}
