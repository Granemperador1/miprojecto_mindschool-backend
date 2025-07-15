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
        Schema::create('progreso_estudiante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('curso_id')->constrained('cursos')->onDelete('cascade');
            $table->foreignId('leccion_id')->nullable()->constrained('lecciones')->onDelete('cascade');
            $table->integer('tiempo_estudiado')->default(0); // en segundos
            $table->boolean('completado')->default(false);
            $table->timestamp('fecha_completado')->nullable();
            $table->integer('puntuacion')->default(0);
            $table->integer('intentos')->default(0);
            $table->timestamp('ultima_actividad')->useCurrent();
            $table->json('actividades_completadas')->nullable(); // array de actividades completadas
            $table->decimal('progreso_porcentual', 5, 2)->default(0); // 0-100%
            $table->enum('estado', ['no_iniciado', 'en_progreso', 'completado', 'abandonado'])->default('no_iniciado');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['estudiante_id', 'curso_id']);
            $table->index(['curso_id', 'estado']);
            $table->index('ultima_actividad');
            $table->unique(['estudiante_id', 'curso_id', 'leccion_id']); // Evitar duplicados
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progreso_estudiante');
    }
};
