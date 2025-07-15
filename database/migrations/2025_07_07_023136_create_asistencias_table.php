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
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('horario_clase_id')->constrained('horarios_clases')->onDelete('cascade');
            $table->date('fecha');
            $table->enum('estado', ['presente', 'ausente', 'tardanza', 'justificada', 'excusa_medica'])->default('ausente');
            $table->text('justificacion')->nullable();
            $table->string('archivo_justificacion')->nullable();
            $table->time('hora_llegada')->nullable();
            $table->time('hora_salida')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['estudiante_id', 'fecha']);
            $table->index(['horario_clase_id', 'fecha']);
            $table->index('estado');
            $table->unique(['estudiante_id', 'horario_clase_id', 'fecha']); // Evitar duplicados
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
