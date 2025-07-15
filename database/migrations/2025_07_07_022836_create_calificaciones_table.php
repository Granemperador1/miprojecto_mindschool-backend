<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('curso_id')->constrained('cursos')->onDelete('cascade');
            $table->foreignId('leccion_id')->nullable()->constrained('lecciones')->onDelete('cascade');
            $table->enum('tipo_evaluacion', ['tarea', 'examen', 'proyecto', 'participacion', 'quiz', 'trabajo_final']);
            $table->decimal('calificacion', 5, 2);
            $table->decimal('peso', 3, 2)->default(1.00); // peso en la calificación final
            $table->text('comentarios')->nullable();
            $table->timestamp('fecha_evaluacion')->useCurrent();
            $table->foreignId('evaluador_id')->constrained('users')->onDelete('cascade');
            $table->enum('estado', ['borrador', 'publicada', 'revisada'])->default('borrador');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['estudiante_id', 'curso_id']);
            $table->index(['curso_id', 'tipo_evaluacion']);
            $table->index('fecha_evaluacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};
