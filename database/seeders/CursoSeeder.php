<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Curso;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CursoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener profesores existentes
        $profesores = User::role('profesor')->get();
        
        if ($profesores->isEmpty()) {
            // Si no hay profesores, crear uno
            $profesor = User::create([
                'name' => 'Dr. Juan Pérez',
                'email' => 'juan.perez@mindschool.com',
                'password' => bcrypt('password123'),
            ]);
            $profesor->assignRole('profesor');
            $profesores = collect([$profesor]);
        }

        // Crear 3 cursos de prueba
        $cursos = [
            [
                'titulo' => 'Introducción a la Programación Web',
                'descripcion' => 'Aprende los fundamentos de HTML, CSS y JavaScript para crear sitios web modernos y responsivos. Este curso te llevará desde cero hasta crear tu primera aplicación web completa.',
                'duracion' => 60, // horas
                'nivel' => 'principiante',
                'precio' => 299.99,
                'estado' => 'activo',
                'instructor_id' => $profesores->first()->id,
                'imagen_url' => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=800',
                'tipo' => 'pago',
                'codigo_invitacion' => 'WEB101'
            ],
            [
                'titulo' => 'Desarrollo de Aplicaciones Móviles con React Native',
                'descripcion' => 'Domina React Native y crea aplicaciones móviles nativas para iOS y Android con un solo código base. Aprende a integrar APIs, manejar estado y publicar en las tiendas.',
                'duracion' => 80,
                'nivel' => 'intermedio',
                'precio' => 399.99,
                'estado' => 'activo',
                'instructor_id' => $profesores->first()->id,
                'imagen_url' => 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=800',
                'tipo' => 'pago',
                'codigo_invitacion' => 'MOBILE201'
            ],
            [
                'titulo' => 'Machine Learning para Principiantes',
                'descripcion' => 'Introducción práctica al machine learning usando Python. Aprende algoritmos básicos, preprocesamiento de datos y cómo crear modelos predictivos reales.',
                'duracion' => 100,
                'nivel' => 'intermedio',
                'precio' => 0.00, // Curso gratuito
                'estado' => 'activo',
                'instructor_id' => $profesores->first()->id,
                'imagen_url' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800',
                'tipo' => 'gratis',
                'codigo_invitacion' => 'ML101'
            ]
        ];

        foreach ($cursos as $cursoData) {
            Curso::create($cursoData);
        }

        $this->command->info('3 cursos de prueba creados exitosamente!');
    }
} 