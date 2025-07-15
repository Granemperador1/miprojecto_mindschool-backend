<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Calificacion;
use App\Models\User;
use App\Models\Curso;
use App\Models\Leccion;

class CalificacionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener usuarios y cursos existentes
        $estudiantes = User::role('estudiante')->take(5)->get();
        $profesores = User::role('profesor')->take(3)->get();
        $cursos = Curso::take(3)->get();

        if ($estudiantes->isEmpty() || $profesores->isEmpty() || $cursos->isEmpty()) {
            $this->command->info('No hay suficientes datos para crear calificaciones. Ejecuta primero los seeders de usuarios y cursos.');
            return;
        }

        $tiposEvaluacion = ['tarea', 'examen', 'proyecto', 'participacion', 'quiz'];
        $estados = ['borrador', 'publicada', 'revisada'];

        foreach ($estudiantes as $estudiante) {
            foreach ($cursos as $curso) {
                // Crear 2-4 calificaciones por estudiante por curso
                $numCalificaciones = rand(2, 4);
                
                for ($i = 0; $i < $numCalificaciones; $i++) {
                    $tipo = $tiposEvaluacion[array_rand($tiposEvaluacion)];
                    $estado = $estados[array_rand($estados)];
                    $calificacion = rand(60, 100); // Calificaciones entre 60 y 100
                    $peso = rand(5, 20) / 100; // Peso entre 0.05 y 0.20

                    // Obtener una lección aleatoria que pertenezca al curso actual
                    $leccion = Leccion::where('curso_id', $curso->id)->inRandomOrder()->first();

                    if (!$leccion) continue; // Si el curso no tiene lecciones, saltamos a la siguiente iteración
                    
                    Calificacion::create([
                        'estudiante_id' => $estudiante->id,
                        'curso_id' => $curso->id,
                        'leccion_id' => $leccion->id,
                        'tipo_evaluacion' => $tipo,
                        'calificacion' => $calificacion,
                        'peso' => $peso,
                        'comentarios' => $this->getComentarioAleatorio($tipo, $calificacion),
                        'evaluador_id' => $profesores->random()->id,
                        'estado' => $estado,
                        'fecha_evaluacion' => now()->subDays(rand(1, 30))
                    ]);
                }
            }
        }

        $this->command->info('Calificaciones creadas exitosamente.');
    }

    private function getComentarioAleatorio($tipo, $calificacion)
    {
        $comentarios = [
            'tarea' => [
                'Excelente trabajo, muy bien estructurado.',
                'Buen esfuerzo, pero puedes mejorar en la presentación.',
                'Trabajo completo y bien desarrollado.',
                'Necesitas revisar algunos conceptos.',
                'Muy buena comprensión del tema.'
            ],
            'examen' => [
                'Demuestra buen dominio de los conceptos.',
                'Algunas respuestas necesitan más precisión.',
                'Excelente rendimiento en el examen.',
                'Revisa los temas de la unidad.',
                'Muy bien preparado para la evaluación.'
            ],
            'proyecto' => [
                'Proyecto muy creativo y bien ejecutado.',
                'Buena idea, pero falta desarrollo.',
                'Excelente trabajo en equipo.',
                'Necesita más investigación.',
                'Proyecto sobresaliente.'
            ],
            'participacion' => [
                'Muy participativo en clase.',
                'Buenas contribuciones al debate.',
                'Participación activa y constructiva.',
                'Necesita participar más en clase.',
                'Excelente participación en las actividades.'
            ],
            'quiz' => [
                'Buen rendimiento en el quiz.',
                'Necesita repasar los conceptos.',
                'Excelente comprensión del material.',
                'Algunas respuestas incorrectas.',
                'Muy bien preparado para el quiz.'
            ]
        ];

        $comentariosTipo = $comentarios[$tipo] ?? $comentarios['tarea'];
        return $comentariosTipo[array_rand($comentariosTipo)];
    }
}
