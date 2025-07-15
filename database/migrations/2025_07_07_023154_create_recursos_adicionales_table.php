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
        Schema::create('recursos_adicionales', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion');
            $table->enum('tipo', ['libro', 'articulo', 'video', 'enlace', 'documento', 'presentacion', 'audio', 'imagen']);
            $table->string('url', 500)->nullable();
            $table->string('archivo_url')->nullable();
            $table->foreignId('curso_id')->nullable()->constrained('cursos')->onDelete('cascade');
            $table->foreignId('leccion_id')->nullable()->constrained('lecciones')->onDelete('cascade');
            $table->boolean('es_obligatorio')->default(false);
            $table->integer('orden')->default(0);
            $table->string('autor')->nullable();
            $table->string('editorial')->nullable();
            $table->string('isbn')->nullable();
            $table->integer('año_publicacion')->nullable();
            $table->decimal('precio', 10, 2)->nullable();
            $table->enum('estado', ['activo', 'inactivo', 'borrador'])->default('activo');
            $table->foreignId('creador_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['curso_id', 'tipo']);
            $table->index(['leccion_id', 'orden']);
            $table->index('es_obligatorio');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recursos_adicionales');
    }
};
