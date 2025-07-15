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
        Schema::create('examenes', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion');
            $table->foreignId('curso_id')->constrained('cursos')->onDelete('cascade');
            $table->foreignId('leccion_id')->nullable()->constrained('lecciones')->onDelete('cascade');
            $table->integer('tiempo_limite')->nullable(); // en minutos
            $table->integer('intentos_permitidos')->default(1);
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_fin')->nullable();
            $table->enum('estado', ['borrador', 'activo', 'cerrado', 'archivado'])->default('borrador');
            $table->integer('puntos_totales')->default(100);
            $table->boolean('mostrar_resultados')->default(true);
            $table->boolean('aleatorizar_preguntas')->default(false);
            $table->foreignId('creador_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['curso_id', 'estado']);
            $table->index('fecha_inicio');
            $table->index('fecha_fin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examenes');
    }
};
