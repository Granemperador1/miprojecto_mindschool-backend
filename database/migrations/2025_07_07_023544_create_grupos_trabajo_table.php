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
        Schema::create('grupos_trabajo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion');
            $table->foreignId('curso_id')->constrained('cursos')->onDelete('cascade');
            $table->integer('max_estudiantes')->nullable();
            $table->enum('estado', ['activo', 'inactivo', 'completado', 'cancelado'])->default('activo');
            $table->string('codigo_acceso')->unique(); // código para unirse al grupo
            $table->boolean('auto_asignacion')->default(false); // si los estudiantes se pueden auto-asignar
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->text('objetivos')->nullable();
            $table->json('configuracion')->nullable(); // configuración específica del grupo
            $table->foreignId('creador_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['curso_id', 'estado']);
            $table->index('codigo_acceso');
            $table->index('fecha_inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupos_trabajo');
    }
};
