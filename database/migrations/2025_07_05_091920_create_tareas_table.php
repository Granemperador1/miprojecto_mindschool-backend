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
        Schema::create('tareas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion');
            $table->datetime('fecha_asignacion');
            $table->datetime('fecha_entrega');
            $table->enum('tipo', ['pdf', 'video', 'enlace', 'documento', 'presentacion']);
            $table->string('archivo_url')->nullable();
            $table->foreignId('curso_id')->constrained()->onDelete('cascade');
            $table->foreignId('leccion_id')->nullable()->constrained('lecciones')->onDelete('cascade');
            $table->enum('estado', ['activa', 'cerrada', 'borrador'])->default('activa');
            $table->integer('puntos_maximos')->default(10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
