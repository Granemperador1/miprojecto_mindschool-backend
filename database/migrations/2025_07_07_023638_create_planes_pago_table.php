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
        Schema::create('planes_pago', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion');
            $table->decimal('precio', 10, 2);
            $table->string('moneda', 10)->default('USD');
            $table->integer('duracion_dias')->nullable(); // null = ilimitado
            $table->json('caracteristicas'); // array de características del plan
            $table->json('cursos_incluidos')->nullable(); // IDs de cursos incluidos
            $table->integer('max_cursos')->nullable(); // máximo de cursos permitidos
            $table->integer('max_estudiantes')->nullable(); // máximo de estudiantes
            $table->boolean('soporte_prioritario')->default(false);
            $table->boolean('certificados')->default(false);
            $table->boolean('activo')->default(true);
            $table->boolean('destacado')->default(false);
            $table->integer('orden')->default(0);
            $table->string('codigo_promocional')->nullable();
            $table->decimal('descuento_porcentual', 5, 2)->default(0);
            $table->date('fecha_inicio_promocion')->nullable();
            $table->date('fecha_fin_promocion')->nullable();
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['activo', 'destacado']);
            $table->index('precio');
            $table->index('codigo_promocional');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planes_pago');
    }
};
