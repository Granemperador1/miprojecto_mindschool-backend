<?php

namespace Database\Factories;

use App\Models\Multimedia;
use App\Models\Leccion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Multimedia>
 */
class MultimediaFactory extends Factory
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
            'tipo' => fake()->randomElement(['video', 'audio', 'documento', 'imagen']),
            'url' => fake()->url(),
            'leccion_id' => Leccion::factory(),
            'orden' => fake()->numberBetween(1, 10),
            'estado' => fake()->randomElement(['activo', 'inactivo']),
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the multimedia is a video.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'video',
            'url' => 'https://example.com/videos/' . fake()->slug() . '.mp4',
        ]);
    }

    /**
     * Indicate that the multimedia is an audio.
     */
    public function audio(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'audio',
            'url' => 'https://example.com/audio/' . fake()->slug() . '.mp3',
        ]);
    }

    /**
     * Indicate that the multimedia is a document.
     */
    public function documento(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'documento',
            'url' => 'https://example.com/documents/' . fake()->slug() . '.pdf',
        ]);
    }

    /**
     * Indicate that the multimedia is an image.
     */
    public function imagen(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'imagen',
            'url' => 'https://example.com/images/' . fake()->slug() . '.jpg',
        ]);
    }

    /**
     * Indicate that the multimedia is active.
     */
    public function activo(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'activo',
        ]);
    }
} 