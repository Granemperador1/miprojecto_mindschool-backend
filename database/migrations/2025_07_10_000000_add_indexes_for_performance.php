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
        // Índices para la tabla cursos
        Schema::table('cursos', function (Blueprint $table) {
            $table->index(['estado', 'created_at'], 'idx_cursos_estado_created');
            $table->index(['instructor_id', 'estado'], 'idx_cursos_instructor_estado');
            $table->index(['nivel', 'estado'], 'idx_cursos_nivel_estado');
            $table->fullText(['titulo', 'descripcion'], 'idx_cursos_fulltext');
        });

        // Índices para la tabla inscripciones
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->index(['user_id', 'estado'], 'idx_inscripciones_user_estado');
            $table->index(['curso_id', 'estado'], 'idx_inscripciones_curso_estado');
            $table->index(['created_at'], 'idx_inscripciones_created');
        });

        // Índices para la tabla lecciones
        Schema::table('lecciones', function (Blueprint $table) {
            $table->index(['curso_id', 'orden'], 'idx_lecciones_curso_orden');
            $table->index(['estado'], 'idx_lecciones_estado');
        });

        // Índices para la tabla tareas
        Schema::table('tareas', function (Blueprint $table) {
            $table->index(['curso_id', 'fecha_entrega'], 'idx_tareas_curso_fecha');
            $table->index(['estado'], 'idx_tareas_estado');
        });

        // Índices para la tabla entregas_tareas
        Schema::table('entregas_tareas', function (Blueprint $table) {
            $table->index(['tarea_id', 'estudiante_id'], 'idx_entregas_tarea_estudiante');
            $table->index(['estado'], 'idx_entregas_estado');
            $table->index(['fecha_entrega'], 'idx_entregas_fecha');
        });

        // Índices para la tabla calificaciones
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->index(['estudiante_id', 'curso_id'], 'idx_calificaciones_estudiante_curso');
            $table->index(['created_at'], 'idx_calificaciones_created');
        });

        // Índices para la tabla asistencias
        Schema::table('asistencias', function (Blueprint $table) {
            $table->index(['estudiante_id', 'fecha'], 'idx_asistencias_estudiante_fecha');
            // $table->index(['curso_id', 'fecha'], 'idx_asistencias_curso_fecha'); // Eliminado porque la columna no existe
        });

        // Índices para la tabla mensajes
        Schema::table('mensajes', function (Blueprint $table) {
            $table->index(['remitente_id', 'destinatario_id'], 'idx_mensajes_remitente_destinatario');
            // $table->index(['leido'], 'idx_mensajes_leido'); // Eliminado porque la columna no existe
            $table->index(['created_at'], 'idx_mensajes_created');
        });

        // Índices para la tabla users
        Schema::table('users', function (Blueprint $table) {
            $table->index(['email'], 'idx_users_email');
            $table->index(['created_at'], 'idx_users_created');
        });

        // Índices para la tabla multimedia
        Schema::table('multimedia', function (Blueprint $table) {
            $table->index(['leccion_id', 'tipo'], 'idx_multimedia_leccion_tipo');
            // $table->index(['curso_id'], 'idx_multimedia_curso'); // Eliminado porque la columna no existe
        });

        // Índices para la tabla recursos_adicionales
        Schema::table('recursos_adicionales', function (Blueprint $table) {
            $table->index(['curso_id', 'tipo'], 'idx_recursos_curso_tipo');
            $table->index(['estado'], 'idx_recursos_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices de cursos
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropIndex('idx_cursos_estado_created');
            $table->dropIndex('idx_cursos_instructor_estado');
            $table->dropIndex('idx_cursos_nivel_estado');
            $table->dropIndex('idx_cursos_fulltext');
        });

        // Eliminar índices de inscripciones
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropIndex('idx_inscripciones_user_estado');
            $table->dropIndex('idx_inscripciones_curso_estado');
            $table->dropIndex('idx_inscripciones_created');
        });

        // Eliminar índices de lecciones
        Schema::table('lecciones', function (Blueprint $table) {
            $table->dropIndex('idx_lecciones_curso_orden');
            $table->dropIndex('idx_lecciones_estado');
        });

        // Eliminar índices de tareas
        Schema::table('tareas', function (Blueprint $table) {
            $table->dropIndex('idx_tareas_curso_fecha');
            $table->dropIndex('idx_tareas_estado');
        });

        // Eliminar índices de entregas_tareas
        Schema::table('entregas_tareas', function (Blueprint $table) {
            $table->dropIndex('idx_entregas_tarea_estudiante');
            $table->dropIndex('idx_entregas_estado');
            $table->dropIndex('idx_entregas_fecha');
        });

        // Eliminar índices de calificaciones
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropIndex('idx_calificaciones_estudiante_curso');
            $table->dropIndex('idx_calificaciones_created');
        });

        // Eliminar índices de asistencias
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropIndex('idx_asistencias_estudiante_fecha');
            // $table->dropIndex('idx_asistencias_curso_fecha'); // Eliminado porque la columna no existe
        });

        // Eliminar índices de mensajes
        Schema::table('mensajes', function (Blueprint $table) {
            $table->dropIndex('idx_mensajes_remitente_destinatario');
            // $table->dropIndex('idx_mensajes_leido'); // Eliminado porque la columna no existe
            $table->dropIndex('idx_mensajes_created');
        });

        // Eliminar índices de users
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email');
            $table->dropIndex('idx_users_created');
        });

        // Eliminar índices de multimedia
        Schema::table('multimedia', function (Blueprint $table) {
            $table->dropIndex('idx_multimedia_leccion_tipo');
            // $table->dropIndex('idx_multimedia_curso'); // Eliminado porque la columna no existe
        });

        // Eliminar índices de recursos_adicionales
        Schema::table('recursos_adicionales', function (Blueprint $table) {
            $table->dropIndex('idx_recursos_curso_tipo');
            $table->dropIndex('idx_recursos_estado');
        });
    }
}; 