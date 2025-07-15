<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Curso;
use App\Models\Tarea;
use App\Models\Inscripcion;
use App\Models\Leccion;
use Illuminate\Support\Facades\Hash;

class EstudianteTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear estudiante de prueba
        $estudiante = User::create([
            'name' => 'María López',
            'email' => 'estudiante@test.com',
            'password' => Hash::make('123456'),
            'avatar_url' => 'https://randomuser.me/api/portraits/women/44.jpg'
        ]);
        $estudiante->assignRole('estudiante');

        // Crear profesor
        $profesor = User::create([
            'name' => 'Prof. Carlos Rodríguez',
            'email' => 'profesor@test.com',
            'password' => Hash::make('123456'),
            'avatar_url' => 'https://randomuser.me/api/portraits/men/32.jpg'
        ]);
        $profesor->assignRole('profesor');

        // Crear curso (materia)
        $curso = Curso::create([
            'titulo' => 'Matemáticas IV',
            'descripcion' => 'Curso avanzado de matemáticas que cubre temas de álgebra lineal, cálculo multivariable y ecuaciones diferenciales.',
            'duracion' => 16,
            'nivel' => 'intermedio',
            'precio' => 0,
            'estado' => 'activo',
            'instructor_id' => $profesor->id,
            'imagen_url' => 'https://images.unsplash.com/photo-1547658719-da2b51169166?q=80&w=2080'
        ]);

        // Crear lección
        $leccion = Leccion::create([
            'titulo' => 'Introducción a Álgebra Lineal',
            'descripcion' => 'Conceptos básicos de matrices y vectores',
            'contenido' => 'Contenido de ejemplo',
            'duracion' => 60,
            'curso_id' => $curso->id,
            'orden' => 1
        ]);

        // Crear tarea
        $tarea = Tarea::create([
            'titulo' => 'Ejercicios de Álgebra Lineal',
            'descripcion' => 'Resolver los ejercicios 1-20 del capítulo 4 sobre matrices y determinantes',
            'fecha_asignacion' => now()->subDays(5),
            'fecha_entrega' => now()->addDays(10),
            'tipo' => 'pdf',
            'curso_id' => $curso->id,
            'leccion_id' => $leccion->id,
            'estado' => 'activa',
            'puntos_maximos' => 10
        ]);

        // Inscribir al estudiante en el curso
        Inscripcion::create([
            'user_id' => $estudiante->id,
            'curso_id' => $curso->id,
            'estado' => 'activo',
            'progreso' => 75
        ]);

        $this->command->info('Datos de prueba creados exitosamente!');
        $this->command->info('Estudiante: estudiante@test.com / 123456');
        $this->command->info('Profesor: profesor@test.com / 123456');
    }
}
