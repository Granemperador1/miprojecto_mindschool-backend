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
        Schema::create('analytics_cursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->onDelete('cascade');
            $table->date('fecha');
            $table->integer('estudiantes_activos')->default(0);
            $table->integer('estudiantes_nuevos')->default(0);
            $table->integer('tiempo_promedio_sesion')->default(0); // en minutos
            $table->integer('lecciones_completadas')->default(0);
            $table->integer('tareas_entregadas')->default(0);
            $table->decimal('calificacion_promedio', 5, 2)->default(0);
            $table->integer('asistencias_totales')->default(0);
            $table->integer('asistencias_presentes')->default(0);
            $table->decimal('tasa_asistencia', 5, 2)->default(0); // porcentaje
            $table->integer('examenes_realizados')->default(0);
            $table->decimal('promedio_examenes', 5, 2)->default(0);
            $table->integer('recursos_descargados')->default(0);
            $table->integer('mensajes_enviados')->default(0);
            $table->json('metricas_adicionales')->nullable(); // métricas específicas del curso
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['curso_id', 'fecha']);
            $table->index('fecha');
            $table->unique(['curso_id', 'fecha']); // Una entrada por curso por día
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_cursos');
    }
};
