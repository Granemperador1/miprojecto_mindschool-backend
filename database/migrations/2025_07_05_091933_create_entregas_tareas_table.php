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
        Schema::create('entregas_tareas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_id')->constrained()->onDelete('cascade');
            $table->foreignId('estudiante_id')->constrained('users')->onDelete('cascade');
            $table->string('archivo_url')->nullable();
            $table->text('comentarios')->nullable();
            $table->decimal('calificacion', 5, 2)->nullable();
            $table->text('comentarios_profesor')->nullable();
            $table->datetime('fecha_entrega');
            $table->enum('estado', ['entregada', 'calificada', 'rechazada'])->default('entregada');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entregas_tareas');
    }
};
