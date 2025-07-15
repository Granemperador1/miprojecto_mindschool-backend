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
        Schema::create('horarios_clases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->onDelete('cascade');
            $table->integer('dia_semana'); // 1=lunes, 7=domingo
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('aula', 100)->nullable();
            $table->enum('tipo_clase', ['presencial', 'virtual', 'hibrida'])->default('presencial');
            $table->string('url_meeting', 500)->nullable(); // para clases virtuales
            $table->string('codigo_meeting', 50)->nullable();
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->date('fecha_inicio_periodo')->nullable();
            $table->date('fecha_fin_periodo')->nullable();
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['curso_id', 'dia_semana']);
            $table->index(['dia_semana', 'hora_inicio']);
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios_clases');
    }
};
