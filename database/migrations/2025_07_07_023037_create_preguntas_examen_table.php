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
        Schema::create('preguntas_examen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')->constrained('examenes')->onDelete('cascade');
            $table->text('pregunta');
            $table->enum('tipo', ['opcion_multiple', 'verdadero_falso', 'texto_libre', 'emparejamiento', 'completar_espacios']);
            $table->json('opciones')->nullable(); // para preguntas de opción múltiple
            $table->text('respuesta_correcta')->nullable();
            $table->json('respuestas_correctas')->nullable(); // para preguntas con múltiples respuestas
            $table->integer('puntos')->default(1);
            $table->integer('orden');
            $table->text('explicacion')->nullable(); // explicación de la respuesta correcta
            $table->boolean('requerida')->default(true);
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['examen_id', 'orden']);
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preguntas_examen');
    }
};
