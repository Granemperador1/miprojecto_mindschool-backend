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
        Schema::create('respuestas_examen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')->constrained('examenes')->onDelete('cascade');
            $table->foreignId('estudiante_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('pregunta_id')->constrained('preguntas_examen')->onDelete('cascade');
            $table->text('respuesta_estudiante');
            $table->json('respuestas_estudiante')->nullable(); // para preguntas con múltiples respuestas
            $table->decimal('puntos_obtenidos', 5, 2)->default(0);
            $table->boolean('es_correcta')->default(false);
            $table->text('comentarios_profesor')->nullable();
            $table->timestamp('fecha_respuesta')->useCurrent();
            $table->integer('tiempo_respuesta')->nullable(); // en segundos
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['examen_id', 'estudiante_id']);
            $table->index(['pregunta_id', 'estudiante_id']);
            $table->index('fecha_respuesta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respuestas_examen');
    }
};
